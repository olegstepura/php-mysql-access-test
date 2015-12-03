<?php

$accessData = [
        [
                'host' => 'localhost',
                //'port' => '3306',
                'db' => 'test',
                'user' => 'test',
                'pass' => '123456',
        ],
        // feel free to add as many access config arrays as needed
];

error_reporting(E_ALL);
ini_set('display_errors', 1);

class MySQLAccessTest
{
        public $host = '127.0.0.1';
        public $port = 3306;
        public $db = 'test';
        public $user = 'test';
        public $pass = '123456';

        public function __construct(array $config) {
                $this->host = (!isset($config['host'])) ?: $this->host;
                $this->port = (!isset($config['port'])) ?: $this->port;
                $this->db   = (!isset($config['db']))   ?: $this->db;
                $this->user = (!isset($config['user'])) ?: $this->user;
                $this->pass = (!isset($config['pass'])) ?: $this->pass;
        }

        private function error($message) {
                throw new Exception($message);
        }

        private function testMysql() {
                $connection = @mysql_connect("$this->host:$this->port", $this->user, $this->pass) or $this->error("Unable to connect:" . mysql_error());
                mysql_select_db($this->db) or $this->error("Could not open the db");
                $queryResult = mysql_query("SHOW TABLES FROM $this->db;");
                $rows = [];
                while($row = mysql_fetch_array($queryResult)) {
                        $rows[] = $row[0];
                }
                return $rows;
        }

        private function testMysqli() {
                $connection = mysqli_connect($this->host, $this->user, $this->pass, $this->db, $this->port) or $this->error("Unable to connect:" . mysqli_connect_error());
                $rows = [];
                if (mysqli_multi_query($connection, "SHOW TABLES FROM $this->db;")) {
                        do {
                                if ($result = mysqli_use_result($connection)) {
                                        while ($row = mysqli_fetch_row($result)) {
                                                $rows[] = $row[0];
                                        }
                                        if (!mysqli_more_results($connection)) {
                                                break;
                                        }
                                        mysqli_free_result($result);
                                }
                        } while (mysqli_next_result($connection));
                }
                mysqli_close($connection);
                return $rows;
        }

        private function testPdo() {
                $dbh = new PDO("mysql:host=$this->host;port=$this->port;dbname=$this->db", $this->user, $this->pass);
                $rows = [];
                foreach($dbh->query("SHOW TABLES FROM $this->db;") as $row) {
                        $rows[] = $row[0];
                }
                return $rows;
        }

        public function check($driverName) {
                try {
                        $name = 'test' . ucfirst($driverName);
                        return [
                                'result' => 'ok',
                                'data' => $this->$name()
                        ];
                } catch (Exception $e) {
                        return [
                                'result' => 'failure',
                                'data' => $e->getMessage()
                        ];
                }
        }

        public function checkAll() {
                $result = [];
                return $result;
        }
}

foreach ($accessData as $config) {
        $tester = new MySQLAccessTest($config);
        $out = ["<b>$tester->user</b>@$tester->host:$tester->port/$tester->db"];
        foreach (['mysql', 'mysqli', 'pdo'] as $driver) {
                $result = $tester->check($driver);
                if ($result['result'] === 'ok') {
                        $out[] = "<u>$driver</u>: [" . join(', ', $result['data']) . ']';
                } else {
                        $out[] = "<span style='color:red; font-weigth: bold'>${result['data']}</span>";
                }
        }
        $out[] = '';
        $out[] = '';
        echo join('<br />', $out);
}
