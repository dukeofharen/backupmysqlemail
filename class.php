<?php
/**
 * The MysqlBackup class
 */
class MysqlBackup {
    /**
     * Host where database is located
     */
    var $host = '';
 
    /**
     * Username used to connect to database
     */
    var $username = '';
 
    /**
     * Password used to connect to database
     */
    var $passwd = '';
 
    /**
     * Database to backup
     */
    var $dbName = '';
 
    /**
     * Database charset
     */
    var $charset = '';
	
	var $email_to = '';
	
	var $email_subject = '';
	
	var $email_message = '';
	
	var $email_from = '';
	
	var $email_from_name = '';
	
	var $mysqli;
 
    /**
     * Constructor initializes database
     */
    function MysqlBackup($host, $username, $passwd, $dbName, $email_to, $email_subject, $email_message, $email_from, $email_from_name, $charset = 'utf8')
    {
        $this->host     = $host;
        $this->username = $username;
        $this->passwd   = $passwd;
        $this->dbName   = $dbName;
        $this->charset  = $charset;
		$this->email_to = $email_to;
		$this->email_subject = $email_subject;
		$this->email_message = $email_message;
		$this->email_from = $email_from;
		$this->email_from_name = $email_from_name;
		$this->mysqli = new mysqli($host, $username, $passwd, $dbName);
 
        $this->initializeDatabase();
    }
 
    protected function initializeDatabase()
    {
        //$conn = mysql_connect($this->host, $this->username, $this->passwd);
        //mysql_select_db($this->dbName, $conn);
        if (! $this->mysqli->set_charset($this->charset))
        {
            $this->mysqli->query('SET NAMES '.$this->charset);
        }
    }
 
    /**
     * Backup the whole database or just some tables
     * Use '*' for whole database or 'table1 table2 table3...'
     * @param string $tables
     */
    public function backupTables($tables = '*', $outputDir = '.')
    {
        try
        {
            /**
            * Tables to export
            */
            if($tables == '*')
            {
                $tables = array();
                $result = $this->mysqli->query('SHOW TABLES');
                while($row = $result->fetch_row())
                {
                    $tables[] = $row[0];
                }
            }
            else
            {
                $tables = is_array($tables) ? $tables : explode(',',$tables);
            }
			$sql = "";
 
            /**
            * Iterate tables
            */
            foreach($tables as $table)
            {
                $result = $this->mysqli->query('SELECT * FROM '.$table);
                $numFields = $result->field_count;;
 
                for ($i = 0; $i < $numFields; $i++)
                {
                    while($row = $result->fetch_assoc())
                    {
                        $sql .= 'INSERT INTO '.$table.' SET ';
						$lines = array();
						foreach($row as $key => $value){
							if($value === null){
								$lines[] = "`".$key."` = NULL";
							}
							else{
								$lines[] = "`".$key."` = '".$this->mysqli->real_escape_string($value)."'";
							}
						}
						$sql .= implode(", ", $lines);
 
                        $sql.= ";\n";
                    }
                }
 
                $sql.="\n\n\n";
            }
        }
        catch (Exception $e)
        {
            var_dump($e->getMessage());
            return false;
        }
 
        return $this->saveFile($sql, $outputDir);
    }
 
    /**
     * Save SQL to file
     * @param string $sql
     */
    protected function saveFile(&$sql, $outputDir = '.')
    {
        if (!$sql) return false;
 
        try
        {
			$filename = 'db-backup-'.$this->dbName.'-'.date("Ymd-His", time()).'.sql';
			$filePath = $outputDir.'/'.$filename;
            $handle = fopen($filePath,'w+');
            fwrite($handle, $sql);
            fclose($handle);
			
			$gzFile = $outputDir.'/'.$filename.'.gz';
			$fp = gzopen($gzFile, 'w9');
			gzwrite($fp, file_get_contents($filePath));
			gzclose($fp);
			
			$sent = MysqlBackup::PhpMailer(array($this->email_to), $this->email_subject, $this->email_message, $this->email_from_name, $this->email_from, array(), array($gzFile));
			if($sent){
				unlink($gzFile);
				unlink($filePath);
			}
        }
        catch (Exception $e)
        {
            var_dump($e->getMessage());
            return false;
        }
 
        return true;
    }
	
	private static function PhpMailer($addresses, $subject, $message, $from_name, $from_email, $bccs = array(), $attachments = array()){
		require_once('phpmailer/class.phpmailer.php');
		$mailer = new PHPMailer();
		$mailer->Host = SMTP_HOST;
		$mailer->Username = SMTP_USER;
		$mailer->Password = SMTP_PASSWORD;
		$mailer->Port = SMTP_PORT;
		$mailer->IsSMTP();
		$mailer->SMTPAuth = SMTP_AUTH;
		$mailer->SMTPSecure = SMTP_SECURE;
		$mailer->From = $from_email;
		$mailer->FromName = $from_name;
		foreach($addresses as $address){
			$mailer->AddAddress($address);
		}
		foreach($bccs as $bcc){
			$mailer->AddBCC($bcc);
		}
		foreach($attachments as $attachment){
			$mailer->AddAttachment($attachment);
		}
		$mailer->CharSet = 'UTF-8';
		$mailer->Subject = $subject;
		$mailer->MsgHTML($message);
		$mailer->SMTPDebug = SMTP_DEBUG;
		return $mailer->Send();
	}
}
?>