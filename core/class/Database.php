<?php

    class Database
    {
        public $connection = null;

        /*
            func: exists()
            desc: checks if the database file exists
        */
        public function exists()
        {
            return file_exists(Site::$f3->get("DATABASE_FILE"));
        }

        /*
            func: create()
            desc: creates a new database
        */
        public function create()
        {
            // get database configurations from f3
            $db_file_path = Site::$f3->get("DATABASE_FILE");
            $db_schema_path = Site::$f3->get("DATABASE_SCHEMA");
            // check if database file already exists
            if($this->exists()) {
                echo "Error (Site::dbCreate): Database file already exists.";
                exit();
            }
            // create the database
            shell_exec("echo .quit | sqlite3 $db_file_path -init $db_schema_path");
            // check if the database file exists
            if(!$this->exists()) {
                echo "Error (Site::dbCreate): Failed to create database.";
                exit();
            }
        }

        /*
            func: connect()
            desc: connects to the database
        */
        public function connect()
        {
            // attempt to create database if it does not already exist
            if(!$this->exists()) {
                $this->create();
            }
            // attempt to connect to the database
            try {
                $this->connection = new DB\SQL("sqlite:" . Site::$f3->get("DATABASE_FILE"));
            } catch(Exception $e) {
                Site::$f3->set("error_information", "Could not connect to databse");
                Site::$f3->error(503);
            }
            // check for invalid schema
            if(count($this->connection->schema("pastes")) == 0) {
                Site::$f3->set("error_information", "Invalid database schema detected");
                Site::$f3->error(503);
            }
        }

        /*
            func: exec($sql, $data)
            desc: executes a sql statement on the database
        */
        public function exec($sql, $data = null)
        {
            if($this->connection == null) {
                Site::$f3->set("error_information", "Database connection unavailable");
                Site::$f3->error(503);
            }
            return $this->connection->exec($sql, $data);
        }
    }

?>
