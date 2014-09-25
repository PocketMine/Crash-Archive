<?php

class Database{

	private static $INSERT_REPORT = <<<QUERY
INSERT INTO crash_reports (plugin, version, build, file, message, line, type, os, reportType, submitDate, reportDate) VALUES
(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
QUERY;


	/** @var \mysqli */
	private $db;

	public function __construct(){
		$this->db = new \mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE);
	}

	public function insertReport(CrashReport $report){
		if($stmt = $this->db->prepare(self::$INSERT_REPORT)){
			$stmt->bind_param("ssississiii",
				$report->getCausingPlugin(),
				$report->getVersion()->get(true),
				$report->getVersion()->getBuild(),
				$report->getFile(),
				$report->getMessage(),
				$report->getLine(),
				$report->getType(),
				$report->getOS(),
				$report->getReportType(),
				time(),
				$report->getDate()
			);

			$stmt->execute();

			return $this->db->insert_id;
		}
		return -1;
	}

	/**
	 * @param string|null $selector
	 * @param string|null $order
	 *
	 * @return bool|mysqli_result
	 */
	public function getReports($selector = null, $order = null){
		return $this->db->query("SELECT * FROM crash_reports " . ($selector !== null ? "WHERE $selector ":"") . ($order !== null ? "ORDER BY $order":"").";");
	}

	public function getReport($id){
		$result = $this->db->query("SELECT * FROM crash_reports WHERE id = ".intval($id).";");
		if($result instanceof \mysqli_result){
			return $result->fetch_assoc();
		}

		return null;
	}

	public function runQuery($query){
		return $this->db->query($query);
	}
}