<?php

namespace nguyenanhung\CodeIgniterDB;
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2015, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package      CodeIgniter
 * @author       EllisLab Dev Team
 * @copyright    Copyright (c) 2008 - 2014, EllisLab, Inc. (http://ellislab.com/)
 * @copyright    Copyright (c) 2014 - 2015, British Columbia Institute of Technology (http://bcit.ca/)
 * @license      http://opensource.org/licenses/MIT	MIT License
 * @link         http://codeigniter.com
 * @since        Version 1.3.0
 * @filesource
 */

/**
 * MySQLi Database Adapter Class
 *
 * Note: _DB is an extender class that the app controller
 * creates dynamically based on whether the query builder
 * class is being used or not.
 *
 * @package        CodeIgniter
 * @subpackage     Drivers
 * @category       Database
 * @author         EllisLab Dev Team
 * @link           http://codeigniter.com/user_guide/database/
 */
class CI_DB_mysqli_driver extends CI_DB
{

    /**
     * Database driver
     *
     * @var    string
     */
    public $dbdriver = 'mysqli';

    /**
     * Compression flag
     *
     * @var    bool
     */
    public $compress = false;

    /**
     * DELETE hack flag
     *
     * Whether to use the MySQL "delete hack" which allows the number
     * of affected rows to be shown. Uses a preg_replace when enabled,
     * adding a bit more processing to all queries.
     *
     * @var    bool
     */
    public $delete_hack = true;

    /**
     * Strict ON flag
     *
     * Whether we're running in strict SQL mode.
     *
     * @var    bool
     */
    public $stricton = false;

    // --------------------------------------------------------------------

    /**
     * Identifier escape character
     *
     * @var    string
     */
    protected $_escape_char = '`';

    // --------------------------------------------------------------------

    /**
     * Database connection
     *
     * @param bool $persistent
     *
     * @return    object
     */
    public function db_connect($persistent = false)
    {
        // Do we have a socket path?
        if ($this->hostname[0] === '/') {
            $hostname = null;
            $port = null;
            $socket = $this->hostname;
        } else {
            // Persistent connection support was added in PHP 5.3.0
            $hostname = ($persistent === true && is_php('5.3'))
                ? 'p:' . $this->hostname : $this->hostname;
            $port = empty($this->port) ? null : $this->port;
            $socket = null;
        }

        $client_flags = ($this->compress === true) ? MYSQLI_CLIENT_COMPRESS : 0;
        $mysqli = mysqli_init();

        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

        if ($this->stricton) {
            $mysqli->options(MYSQLI_INIT_COMMAND, 'SET SESSION sql_mode="STRICT_ALL_TABLES"');
        }

        if (is_array($this->encrypt)) {
            $ssl = array();
            empty($this->encrypt['ssl_key']) or $ssl['key'] = $this->encrypt['ssl_key'];
            empty($this->encrypt['ssl_cert']) or $ssl['cert'] = $this->encrypt['ssl_cert'];
            empty($this->encrypt['ssl_ca']) or $ssl['ca'] = $this->encrypt['ssl_ca'];
            empty($this->encrypt['ssl_capath']) or $ssl['capath'] = $this->encrypt['ssl_capath'];
            empty($this->encrypt['ssl_cipher']) or $ssl['cipher'] = $this->encrypt['ssl_cipher'];

            if (!empty($ssl)) {
                if (!empty($this->encrypt['ssl_verify']) && defined('MYSQLI_OPT_SSL_VERIFY_SERVER_CERT')) {
                    $mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
                }

                $client_flags |= MYSQLI_CLIENT_SSL;
                $mysqli->ssl_set(
                    isset($ssl['key']) ? $ssl['key'] : null,
                    isset($ssl['cert']) ? $ssl['cert'] : null,
                    isset($ssl['ca']) ? $ssl['ca'] : null,
                    isset($ssl['capath']) ? $ssl['capath'] : null,
                    isset($ssl['cipher']) ? $ssl['cipher'] : null
                );
            }
        }

        if ($mysqli->real_connect($hostname, $this->username, $this->password, $this->database, $port, $socket, $client_flags)) {
            // Prior to version 5.7.3, MySQL silently downgrades to an unencrypted connection if SSL setup fails
            if (
                ($client_flags & MYSQLI_CLIENT_SSL)
                && version_compare($mysqli->client_info, '5.7.3', '<=')
                && empty($mysqli->query("SHOW STATUS LIKE 'ssl_cipher'")->fetch_object()->Value)
            ) {
                $mysqli->close();
                $message = 'MySQLi was configured for an SSL connection, but got an unencrypted connection instead!';

                return ($this->db->db_debug) ? $this->db->display_error($message, '', true) : false;
            }

            return $mysqli;
        }

        return false;
    }

    // --------------------------------------------------------------------

    /**
     * Reconnect
     *
     * Keep / reestablish the db connection if no queries have been
     * sent for a length of time exceeding the server's idle timeout
     *
     * @return    void
     */
    public function reconnect()
    {
        if ($this->conn_id !== false && $this->conn_id->ping() === false) {
            $this->conn_id = false;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Select the database
     *
     * @param string $database
     *
     * @return    bool
     */
    public function db_select($database = '')
    {
        if ($database === '') {
            $database = $this->database;
        }

        if ($this->conn_id->select_db($database)) {
            $this->database = $database;

            return true;
        }

        return false;
    }

    // --------------------------------------------------------------------

    /**
     * Set client character set
     *
     * @param string $charset
     *
     * @return    bool
     */
    protected function _db_set_charset($charset)
    {
        return $this->conn_id->set_charset($charset);
    }

    // --------------------------------------------------------------------

    /**
     * Database version number
     *
     * @return    string
     */
    public function version()
    {
        if (isset($this->data_cache['version'])) {
            return $this->data_cache['version'];
        }

        return $this->data_cache['version'] = $this->conn_id->server_info;
    }

    // --------------------------------------------------------------------

    /**
     * Execute the query
     *
     * @param string $sql an SQL query
     *
     * @return    mixed
     */
    protected function _execute($sql)
    {
        return $this->conn_id->query($this->_prep_query($sql));
    }

    // --------------------------------------------------------------------

    /**
     * Prep the query
     *
     * If needed, each database adapter can prep the query string
     *
     * @param string $sql an SQL query
     *
     * @return    string
     */
    protected function _prep_query($sql)
    {
        // mysqli_affected_rows() returns 0 for "DELETE FROM TABLE" queries. This hack
        // modifies the query so that it a proper number of affected rows is returned.
        if ($this->delete_hack === true && preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $sql)) {
            return trim($sql) . ' WHERE 1=1';
        }

        return $sql;
    }

    // --------------------------------------------------------------------

    /**
     * Begin Transaction
     *
     * @return    bool
     */
    protected function _trans_begin()
    {
        $this->conn_id->autocommit(false);

        return is_php('5.5')
            ? $this->conn_id->begin_transaction()
            : $this->simple_query('START TRANSACTION'); // can also be BEGIN or BEGIN WORK
    }

    // --------------------------------------------------------------------

    /**
     * Commit Transaction
     *
     * @return    bool
     */
    protected function _trans_commit()
    {
        if ($this->conn_id->commit()) {
            $this->conn_id->autocommit(true);

            return true;
        }

        return false;
    }

    // --------------------------------------------------------------------

    /**
     * Rollback Transaction
     *
     * @return    bool
     */
    protected function _trans_rollback()
    {
        if ($this->conn_id->rollback()) {
            $this->conn_id->autocommit(true);

            return true;
        }

        return false;
    }

    // --------------------------------------------------------------------

    /**
     * Platform-dependant string escape
     *
     * @param string
     *
     * @return    string
     */
    protected function _escape_str($str)
    {
        return $this->conn_id->real_escape_string($str);
    }

    // --------------------------------------------------------------------

    /**
     * Affected Rows
     *
     * @return    int
     */
    public function affected_rows()
    {
        return $this->conn_id->affected_rows;
    }

    // --------------------------------------------------------------------

    /**
     * Insert ID
     *
     * @return    int
     */
    public function insert_id()
    {
        return $this->conn_id->insert_id;
    }

    // --------------------------------------------------------------------

    /**
     * List table query
     *
     * Generates a platform-specific query string so that the table names can be fetched
     *
     * @param bool $prefix_limit
     *
     * @return    string
     */
    protected function _list_tables($prefix_limit = false)
    {
        $sql = 'SHOW TABLES FROM ' . $this->escape_identifiers($this->database);

        if ($prefix_limit !== false && $this->dbprefix !== '') {
            return $sql . " LIKE '" . $this->escape_like_str($this->dbprefix) . "%'";
        }

        return $sql;
    }

    // --------------------------------------------------------------------

    /**
     * Show column query
     *
     * Generates a platform-specific query string so that the column names can be fetched
     *
     * @param string $table
     *
     * @return    string
     */
    protected function _list_columns($table = '')
    {
        return 'SHOW COLUMNS FROM ' . $this->protect_identifiers($table, true, null, false);
    }

    // --------------------------------------------------------------------

    /**
     * Returns an object with field data
     *
     * @param string $table
     *
     * @return    array
     */
    public function field_data($table)
    {
        if (($query = $this->query('SHOW COLUMNS FROM ' . $this->protect_identifiers($table, true, null, false))) === false) {
            return false;
        }
        $query = $query->result_object();

        $retval = array();
        for ($i = 0, $c = count($query); $i < $c; $i++) {
            $retval[$i] = new stdClass();
            $retval[$i]->name = $query[$i]->Field;

            sscanf($query[$i]->Type, '%[a-z](%d)',
                   $retval[$i]->type,
                   $retval[$i]->max_length
            );

            $retval[$i]->default = $query[$i]->Default;
            $retval[$i]->primary_key = (int) ($query[$i]->Key === 'PRI');
        }

        return $retval;
    }

    // --------------------------------------------------------------------

    /**
     * Error
     *
     * Returns an array containing code and message of the last
     * database error that has occurred.
     *
     * @return    array
     */
    public function error()
    {
        if (!empty($this->conn_id->connect_errno)) {
            return array(
                'code'    => $this->conn_id->connect_errno,
                'message' => is_php('5.2.9') ? $this->conn_id->connect_error : mysqli_connect_error()
            );
        }

        return array('code' => $this->conn_id->errno, 'message' => $this->conn_id->error);
    }

    // --------------------------------------------------------------------

    /**
     * FROM tables
     *
     * Groups tables in FROM clauses if needed, so there is no confusion
     * about operator precedence.
     *
     * @return    string
     */
    protected function _from_tables()
    {
        if (!empty($this->qb_join) && count($this->qb_from) > 1) {
            return '(' . implode(', ', $this->qb_from) . ')';
        }

        return implode(', ', $this->qb_from);
    }

    // --------------------------------------------------------------------

    /**
     * Close DB Connection
     *
     * @return    void
     */
    protected function _close()
    {
        $this->conn_id->close();
    }

}
