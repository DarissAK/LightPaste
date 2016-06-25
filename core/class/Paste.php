<?php

    class Paste extends \DB\SQL\Mapper
    {
        public $error = null;
        public $error_http_code = null;

        public function __construct($id = null)
        {
            parent::__construct(Site::$db->connection, "pastes");
            if($id != null) {
                $type = gettype($id);
                if($type == "string") {
                    $this->load(array("access_id = ?", $id));
                }
            }
        }

        /*
            func: create($data)
            desc: creates a new paste
        */
        public function create($data)
        {
            $data_new = array();
            $time = time();
            // process text
            if($data["text"] == "") {
                $this->error = "Text can not be empty.";
                $this->error_http_code = 400;
                return false;
            }
            $data_new["text"] = $data["text"];
            // check text size
            $max_size = Site::$f3->get("PASTE_MAX_SIZE");
            if($max_size > 0 and mb_strlen($data_new["text"]) > $max_size) {
                $this->error = "Paste too large. Must not be greater than "
                    . Util::formatDataSize($max_size);
                $this->error_http_code = 413;
                return false;
            }
            // process language
            if(!array_key_exists($data["language"], Site::$f3->get("site_languages"))) {
                $data_new["language"] = "";
            } else {
                $data_new["language"] = $data["language"];
            }
            // set visibility
            if($data["visibility"] == "private") {
                $data_new["private"] = 1;
            }
            // set expiration
            $expirations = Site::$f3->get("site_expirations");
            if(array_key_exists($data["expiration"], $expirations) and $data["expiration"] != "Never") {
                $data_new["expiration"] = $time + $expirations[$data["expiration"]];
            }
            // set snap
            if($data["snap"] == "true") {
                $data_new["snap"] = 1;
            }
            // set password
            if($data["password"] != null and Site::$f3->get("PASTE_PASSWORD_ENABLED")) {
                $min = Site::$f3->get("PASTE_PASSWORD_MIN_LENGTH");
                $max = Site::$f3->get("PASTE_PASSWORD_MAX_LENGTH");
                if(strlen($data["password"]) < $min) {
                    $this->error = "Password too short. Must not be less than $min characters.";
                    $this->error_http_code = 400;
                    return false;
                }
                if(strlen($data["password"]) > $max) {
                    $this->error = "Password too long. Must not be more than $max characters.";
                    $this->error_http_code = 400;
                    return false;
                }
                // hash the password
                $crypt = \Bcrypt::instance();
                $hash = $crypt->hash($data["password"], null, 12);
                $data_new["password"] = $hash;
            }
            // set ip address
            $data_new["ipaddress"] = Site::$f3->get("IP");
            // set paste time
            $data_new["time"] = $time;
            // generate paste text checksums
            $data_new["md5"] = md5($data_new["text"]);
            $data_new["sha1"] = sha1($data_new["text"]);
            // get the mime content type of the text
            $finfo = new finfo(FILEINFO_MIME);
            $parts = explode(";", $finfo->buffer($data_new["text"]));
            $data_new["content_type"] = $parts[0];
            $data_new["content_charset"] = str_replace(" charset=", "", $parts[1]);
            $data_new["content_length"] = mb_strlen($data_new["text"], "8bit");
            // get the current largest paste id
            $result = Site::$db->exec("SELECT MAX(id) AS id FROM pastes;");
            $max_id = $result[0]["id"];
            if($max_id == NULL) {
                $max_id = 1;
            }
            // generate a new access id for the paste
            $salt = bin2hex(openssl_random_pseudo_bytes(32));
            $hashids = new Hashids\Hashids($salt, 4);
            $data_new["access_id"] = $hashids->encode($max_id);
            // insert the new paste into the database
            $this->copyfrom($data_new);
            $this->save();
            // send email
            Site::$f3->set("email_paste_id", $data_new["access_id"]);
            $email_template = new Template;
            Util::sendMail("New Paste", $email_template->render("templates/email/newpaste.html"));
            return true;
        }

        /*
            func: countView()
            desc: increments the total number of views for a specific paste
        */
        public function countView()
        {
            // get viewcount delay from site configuration
            $delay = Site::$f3->get("PASTE_VIEWCOUNT_DELAY");
            // get current time
            $time = time();
            // don't count the view if the paste has the snap setting enabled
            // and has reached max hits
            if($this->snap == 1 and $this->hits == 2) {
                $this->delete();
                return;
            }
            if($this->expiration != 0 and $this->expiration <= $time) {
                $this->delete();
                return;
            }
            // increase the paste's hit count by 1
            $this->hits++;
            // generate a hash of the paste's access and the client's ip address
            // to use as a unique identifier for paste view logs
            $hash = hash("sha256", $this->access_id . Site::$f3->get("IP"));
            // check the view logs rows containing the hash
            $rows = Site::$db->exec("SELECT time FROM viewlogs WHERE hash = ?;",
                array(1=> $hash)
            );
            // if no rows were found, increase the paste's view count by one and
            // add a new entry to the view logs
            switch(count($rows)) {
                case 0:
                    $this->views++;
                    Site::$db->exec("INSERT INTO viewlogs (hash, time) VALUES(?, ?);",
                        array(1 => $hash, 2 => $time)
                    );
                    break;
                case 1:
                    if($rows[0]["time"] + $delay < $time) {
                        $this->views++;
                        Site::$db->exec("UPDATE viewlogs SET time = ? WHERE hash = ?;",
                            array(1 => $time, 2 => $hash)
                        );
                    }
                    break;
            }
            // update the paste in the database
            $this->save();
        }

        /*
            func: delete()
            desc: deletes the paste from the database
        */
        public function delete()
        {
            foreach($this->fields() as $key=>$value) {
                if($value != "id" and $value != "access_id") {
                    $this->set($value, null);
                }
            }
            $this->set("deleted", 1);
            $this->save();
        }

        /*
            func: setContentHeaders()
            desc: sets headers relating to the paste's content
        */
        public function setContentHeaders()
        {
            header("Content-type: " . $this->get("content_type") .
                "; charset=" . $this->get("content_charset"));
            header("Content-length: " . $this->get("content_length"));
        }

        /*
            func: setAuthed($bool)
            desc: sets whether or not the client has been authorized to view the paste
        */
        public function setAuthed($bool)
        {
            Site::$f3->set("SESSION.paste_" . $this->access_id . "_authed", $bool);
        }

        /*
            func: getAuthed()
            desc: gets whether or not the client has been authorized to view the paste
        */
        public function getAuthed()
        {
            if($this->password == null) {
                return true;
            }
            $var = Site::$f3->get("SESSION.paste_" . $this->access_id . "_authed");
            return $var != null and $var;
        }

        /*
            func: checkPassword($password)
            desc: checks the specified password against the paste's password
        */
        public function checkPassword($password)
        {
            if($this->get("password") != null) {
                $crypt = \Bcrypt::instance();
                if($crypt->verify($password, $this->get("password"))) {
                    $this->setAuthed(true);
                }
            }
        }

    }

?>
