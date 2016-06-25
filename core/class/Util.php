<?php

    class Util
    {
        static function processUpload($files)
        {
            $file = $files["file"];
            if($file["error"] === 0) {
                // get the contents of the file
                $text = file_get_contents($file["tmp_name"]);
                if($text !== false) {
                    $language = Site::$f3->get("POST.language");
                    Site::$f3->set("POST.text", $text);
                    if($language == null or $language == "none") {
                        Site::$f3->set("POST.language", self::getFileLanguage($file["name"]));
                    }
                    $paste = new Paste;
                    if($paste->create(Site::$f3->get("POST"))) {
                        return $paste->get("access_id");
                    } else {
                        $error_info = "";
                        if($paste->error) {
                            $error_info = $paste->error;
                        }
                        $error_code = 500;
                        if($paste->error_http_code) {
                            $error_code = $paste->error_http_code;
                        }
                        self::setErrorInfo($error_info);
                        Site::$f3->error($error_code);
                    }
                } else {
                    Site::$f3->error(500);
                }
            } else {
                if($file["error"] != 4) {
                    Site::$f3->error(409);
                }
            }
        }

        static function getFileLanguage($file)
        {
            $language = "";
            $parts = pathinfo($file);
            if(array_key_exists("extension", $parts)) {
                foreach(Site::$f3->get("site_languages") as $lang_key=>$lang_data) {
                    if($lang_data["file_extension"] == $parts["extension"]) {
                        $language = $lang_key;
                        break;
                    }
                    if(isset($lang_data["extra_file_extensions"])
                    and in_array($parts["extension"],
                    $lang_data["extra_file_extensions"])) {
                        $language = $lang_key;
                        break;
                    }
                }
            }
            return $language;
        }

        /*
            func: readSetting($setting)
            desc: reads a setting that is expected to have a
                  value of either 0 or 1
        */
        static function readSetting($setting)
        {
            if(isset($_COOKIE[$setting])) {
                if($_COOKIE[$setting] == 1) {
                    Site::$f3->set($setting, "true");
                } else {
                    Site::$f3->set($setting, "false");
                }
            } else {
                Site::$f3->set($setting, "true");
            }
        }

        /*
            func: getClientSettings($f3)
            desc: gets settings stored in client cookies
        */
        static function getClientSettings()
        {
            // parse toggle settings
            self::readSetting("editor_line_numbers");
            self::readSetting("editor_line_wrapping");
            self::readSetting("editor_smart_indent");
            self::readSetting("editor_match_brackets");
            self::readSetting("editor_match_tags");
            self::readSetting("editor_highlight_active_line");
            self::readSetting("editor_highlight_occurrences");
            self::readSetting("editor_vertical_ruler");
            self::readSetting("editor_folding");
            // set editor tab size
            if(isset($_COOKIE["editor_tab_size"])) {
                $tabsize = intval($_COOKIE["editor_tab_size"]);
                $min = Site::$f3->get("EDITOR_MINIMUM_TABSIZE");
                $max = Site::$f3->get("EDITOR_MAXIMUM_TABSIZE");
                if($tabsize > $max) {
                    $tabsize = $max;
                } elseif($tabsize < $min) {
                    $tabsize = $min;
                }
                Site::$f3->set("editor_tab_size", $tabsize);
            } else {
                Site::$f3->set("editor_tab_size", Site::$f3->get("EDITOR_DEFAULT_TABSIZE"));
            }
            // set editor blink rate
            if(isset($_COOKIE["editor_cursor_blinkrate"])) {
                Site::$f3->set("editor_cursor_blinkrate", intval($_COOKIE["editor_cursor_blinkrate"]));
            } else {
                Site::$f3->set("editor_cursor_blinkrate", Site::$f3->get("EDITOR_DEFAULT_BLINKRATE"));
            }
            // set editor font size
            if(isset($_COOKIE["editor_font_size"])) {
                Site::$f3->set("editor_font_size", intval($_COOKIE["editor_font_size"]));
            } else {
                Site::$f3->set("editor_font_size", Site::$f3->get("EDITOR_DEFAULT_FONTSIZE"));
            }
            if(isset($_COOKIE["editor_vbarpos"])) {
                $pos = intval($_COOKIE["editor_vbarpos"]);
                $min = Site::$f3->get("EDITOR_MINIMUM_VBARPOS");
                $max = Site::$f3->get("EDITOR_MAXIMUM_VBARPOS");
                if($pos > $max) {
                    $pos = $max;
                } elseif($pos < $min) {
                    $pos = $min;
                }
                Site::$f3->set("editor_vbarpos", $pos);
            } else {
                Site::$f3->set("editor_vbarpos", Site::$f3->get("EDITOR_DEFAULT_VBARPOS"));
            }
            $theme = false;
            $themes = Site::$f3->get("site_themes");
            if(isset($_COOKIE["site_theme"])) {
                $theme = $themes[$_COOKIE["site_theme"]];
            }
            if($theme) {
                Site::$f3->set("site_theme", $theme);
            } else {
                Site::$f3->set("site_theme", $themes[Site::$f3->get("SITE_DEFAULT_THEME")]);
            }
        }

        /*
            func: formatDataSize($bytes)
            desc: formats the given data size into a string
            source: http://stackoverflow.com/questions/5501427/php-filesize-mb-kb-conversion
        */
        static function formatDataSize($bytes)
        {
            if($bytes >= 1073741824) {
                $bytes = number_format($bytes / 1073741824, 2) . ' GB';
            } elseif($bytes >= 1048576) {
                $bytes = number_format($bytes / 1048576, 2) . ' MB';
            } elseif($bytes >= 1024) {
                $bytes = number_format($bytes / 1024, 2) . ' KB';
            } elseif($bytes > 1) {
                $bytes = $bytes . ' bytes';
            } elseif($bytes == 1) {
                $bytes = $bytes . ' byte';
            } else {
                $bytes = '0 bytes';
            }
            return $bytes;
        }

        /*
            func: logIP($ip, $type, $modifier)
            desc: logs an action performed by the client
        */
        static function logIP($ip, $type, $modifier)
        {
            // get current time
            $time = time();
            // check the table for the specified ip address
            $result = Site::$db->exec("SELECT ipaddress FROM iplogs WHERE ipaddress = ?;",
                array(1 => $ip)
            );
            if(gettype($result) == "array") {
                // if a record was found, run an update query
                if(count($result) == 1) {
                    Site::$db->exec("UPDATE iplogs SET $type = ? WHERE ipaddress = ?;",
                        array(1 => ($time + $modifier), 2 => $ip)
                    );
                // if no record was found, insert a new record
                } else {
                    Site::$db->exec("INSERT INTO iplogs (ipaddress, $type) VALUES(?, ?);",
                        array(1 => $ip, 2 => ($time + $modifier))
                    );
                }
            }
            // delete old logs
            Site::$db->exec("DELETE FROM iplogs WHERE paste_time < :time;",
                array(":time" => $time)
            );
        }

        /*
            func: checkIPLogs($ip, $field)
            desc: checks the ip logs for relevant data
        */
        static function checkIPLogs($ip, $field)
        {
            // check the table for the specified ip address
            $result = Site::$db->exec("SELECT $field FROM iplogs WHERE ipaddress = ?;",
                array(1 => $ip)
            );
            // if a record was found, calculate wait time
            if(gettype($result) == "array" and count($result) == 1) {
                $time = time();
                if($result[0][$field] > $time) {
                    return $result[0][$field] - $time;
                }
            }
            return true;
        }

        static function setErrorInfo($info)
        {
            Site::$f3->set("error_info", $info);
        }

        static function sendMail($subject, $body)
        {
            if(!Site::$f3->get("SITE_EMAIL_ENABLED")) {
                return;
            }
            Site::$db->exec("INSERT INTO email_queue (subject, body) VALUES(?, ?);",
                array(1 => $subject, 2 => $body));
        }

        static function isAPI()
        {
            return array_key_exists("X-Lightpaste-Api", Site::$f3->get("HEADERS"));
        }

        static function apiOutput($data)
        {
            header("Content-type: application/json; charset=utf-8");
            echo json_encode($data, JSON_PRETTY_PRINT);
        }

    }

?>
