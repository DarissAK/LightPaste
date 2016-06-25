<?php

    class Site
    {
        static $f3 = null;
        static $db = null;

        /*
            func: initialize($f3)
            desc: initializes the site
        */
        static function initialize($f3)
        {
            self::$f3 = $f3;
            self::$db = new Database();
            self::$db->connect();
            // get supported languages
            $langs = parse_ini_file("../core/data/languages.ini", true);
            ksort($langs);
            self::$f3->set("site_languages", $langs);
            // get paste expirations
            self::$f3->set("site_expirations",
                parse_ini_file("../core/data/expirations.ini", true));
            self::$f3->set("site_themes",
                    parse_ini_file("../core/data/themes.ini", true));
            // get client settings from cookies
            Util::getClientSettings();
            // check for copy text
            if(self::$f3->get("SESSION.copy_text") != null) {
                self::$f3->set("editor_text", self::$f3->get("SESSION.copy_text"));
                self::$f3->clear("SESSION.copy_text");
            }
        }

        /*
            func: onError()
            desc: handles site errors
        */
        static function onError()
        {
            if(Util::isAPI()) {
                Util::apiOutput(array(
                    "error" => true,
                    "error_code" => self::$f3->get("ERROR.code"),
                    "error_status" => self::$f3->get("ERROR.status"),
                    "error_info" => self::$f3->get("error_info")
                ));
            } else {
                $template = new Template;
                echo $template->render("templates/error.html");
            }
        }

        static function main()
        {
            self::$f3->set("editor_readonly", "false");
            self::$f3->set("editor_mode", "''");
            self::$f3->set("paste_authed", true);
            $template = new Template;
            echo $template->render("templates/main.html");
        }

        static function paste()
        {
            $method = self::$f3->get("SERVER.REQUEST_METHOD");
            if($method == "GET") {
                $id = self::$f3->get("PARAMS.id");
                $mode = self::$f3->get("PARAMS.mode");
                if($id != null) {
                    // create paste object
                    $paste = new Paste($id);
                    // check if paste is valid
                    if($paste->get("access_id") == null) {
                        self::$f3->error(404);
                    }
                    // count paste view
                    $paste->countView();
                    // check if the paste has been deleted
                    if($paste->deleted == 1) {
                        self::$f3->error(404);
                    }
                    if($mode != null) {
                        if(!$paste->getAuthed()) {
                            self::$f3->reroute("/" . $paste->get("access_id"));
                        }
                        if($mode == "raw") {
                            $paste->setContentHeaders();
                            // set content type header to text/plain if the paste's
                            // content type is a variation text/*
                            $content_type = $paste->get("content_type");
                            if(strpos($content_type, "text/") !== false) {
                                header("Content-type: text/plain; charset="
                                    . $paste->get("content_charset"));
                            }
                            echo $paste->get("text");
                            exit();
                        } elseif($mode == "copy") {
                            // store the paste's contents in the client's session
                            self::$f3->set("SESSION.copy_text", $paste->get("text"));
                            self::$f3->reroute("/");
                        } elseif($mode == "download") {
                            // set default file extension
                            $extension = "";
                            // get the paste's file extension if it has a language setting
                            if($paste->get("language") != "") {
                                // get the paste's file extension
                                $language_data = self::$f3->get("site_languages")[$paste->get("language")];
                                if(isset($language_data["file_extension"])) {
                                    $extension = $language_data["file_extension"];
                                }
                            }
                            // create a temporary file for the download
                            $file = tempnam(NULL, "txt");
                            // write the paste's contents to the file
                            $handle = fopen($file, "w");
                            fwrite($handle, $paste->get("text"));
                            fclose($handle);
                            // set page headers containing information about the file
                            $paste->setContentHeaders();
                            header("Content-Disposition: attachment; filename=\"$id.$extension\"");
                            // read the contents of the file
                            readfile($file);
                            // remove the file
                            unlink($file);
                            exit();
                        } else {
                            self::$f3->error(404);
                        }
                    }
                    // set page title
                    self::$f3->set("page_title", $paste->get("access_id"));
                    // set paste variables
                    self::$f3->set("paste_access_id", $paste->get("access_id"));
                    if($paste->getAuthed()) {
                        self::$f3->set("paste_authed", true);
                        self::$f3->set("paste_date", date("M d, Y", $paste->get("time")));
                        self::$f3->set("paste_time", date("g:i A", $paste->get("time")));
                        self::$f3->set("paste_views", number_format($paste->get("views")));
                        self::$f3->set("paste_size", Util::formatDataSize($paste->get("content_length")));
                        self::$f3->set("paste_md5", $paste->get("md5"));
                        self::$f3->set("paste_sha1",$paste->get("sha1"));
                        self::$f3->set("paste_private", $paste->get("private"));
                        self::$f3->set("paste_snap", $paste->get("snap"));
                        // set misc editor variables
                        self::$f3->set("editor_text", $paste->get("text"));
                        self::$f3->set("editor_readonly", "true");
                        // set paste language and editor mode
                        $language = $paste->get("language");
                        self::$f3->set("paste_language", $language);
                        $language_website = false;
                        $mode = '""';
                        if($language != "" and $language != null) {
                            $languages = self::$f3->get("site_languages");
                            $language_array = $languages[$language];
                            if($language_array) {
                                $mode = '"' . $language_array["mode"] . '"' or '""';
                                if(isset($language_array["mode_complex"])) {
                                    $mode = $language_array["mode_complex"];
                                }
                                if(isset($language_array["website"])) {
                                    $language_website = $language_array["website"];
                                }
                            }
                        }
                        // set paste language website
                        if($language_website) {
                            self::$f3->set("paste_language_website", $language_website);
                        }
                        self::$f3->set("editor_mode", $mode);
                    }
                    if(Util::isAPI()) {
                        $paste->checkPassword(self::$f3->get("HEADERS.X-Lightpaste-Password"));
                        if(!$paste->getAuthed()) {
                            Util::setErrorInfo("Password required.");
                            Site::$f3->error(403);
                        } else {
                            Util::apiOutput(array(
                                "id" => $paste->get("access_id"),
                                "text" => $paste->get("text"),
                                "language" => $paste->get("language"),
                                "time" => $paste->get("time"),
                                "views" => $paste->get("views"),
                                "size" => $paste->get("content_length"),
                                "private" => $paste->get("private"),
                                "snap" => $paste->get("snap"),
                                "md5" => $paste->get("md5"),
                                "sha1" => $paste->get("sha1")
                            ));
                        }
                    } else {
                        // render page template
                        $template = new Template;
                        echo $template->render("templates/main.html");
                    }
                }
            } elseif($method == "POST") {
                $id = self::$f3->get("PARAMS.id");
                $mode = self::$f3->get("PARAMS.mode");
                if($id and $mode and $mode == "auth") {
                    $password = self::$f3->get("POST.password");
                    // create paste object
                    $paste = new Paste($id);
                    // check if paste is valid
                    if($paste->get("access_id") == null) {
                        self::$f3->error(404);
                    }
                    $paste->checkPassword($password);
                    self::$f3->reroute("/$id");
                } else {
                    $ip = self::$f3->get("IP");
                    // check ip logs
                    $wait_time = Util::checkIPLogs($ip, "paste_time");
                    if($wait_time !== true) {
                        Util::setErrorInfo("You must wait $wait_time second(s) before creating another paste.");
                        self::$f3->error(403);
                    }
                    $access_id = false;
                    // set default error information
                    Util::setErrorInfo("An unknown error occurred while trying to create the paste.");
                    $error_code = 500;
                    // check for file upload
                    $files = self::$f3->get("FILES");
                    if($files != null and $files["file"]["error"] === 0
                    and self::$f3->get("SITE_FILE_UPLOAD_ENABLED")) {
                        $access_id = Util::processUpload($files);
                    } else {
                        $paste = new Paste;
                        if($paste->create(self::$f3->get("POST"))) {
                            $access_id = $paste->get("access_id");
                            if($access_id == null) {
                                Util::setErrorInfo($error);
                                self::$f3->error($error_code);
                            }
                        } else {
                            if($paste->error) {
                                Util::setErrorInfo($paste->error);
                            }
                            if($paste->error_http_code) {
                                $error_code = $paste->error_http_code;
                            }
                            self::$f3->error($error_code);
                        }
                    }
                    // log the paste creation and redirect the client
                    Util::logIP($ip, "paste_time", self::$f3->get("PASTE_CREATION_DELAY"));
                    if(Util::isAPI()) {
                        Util::apiOutput(array(
                            "paste_id" => $access_id
                        ));
                    } else {
                        self::$f3->reroute("/$access_id");
                    }
                }
            }
        }

    }

?>
