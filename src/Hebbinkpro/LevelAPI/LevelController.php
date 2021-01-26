<?php


namespace Hebbinkpro\LevelAPI;

use _64FF00\PureChat\PureChat;
use pocketmine\Player;
use Hebbinkpro\LevelAPI\events\LevelChangeEvent;

class LevelController
{
	private $name;
	private $level;
	private $xp;
	private $xpNextLevel;
	private $totalXp;

	public function __construct(string $username)
	{
		$this->name = $username;
		$this->level = self::getLevel($username);
		$this->xp = self::getXp($username);
		$this->xpNextLevel = self::getXpNextLevel($this->level);
		$this->totalXp = self::getTotalXp($this->name);
	}

	public function name(){
		$this->name;
	}
	public function level(){
		return $this->level;
	}
	public function xp(){
		return $this->xp;
	}
	public function totalXp(){
		return $this->totalXp;
	}
	public function xpNextLevel(){
		return $this->xpNextLevel;
	}

	//user functions

	/**
	 * @param string $username
	 * @return false|LevelController
	 */
	public static function addUser(string $username)
	{
		if (self::existsUser($username)) {
			return false;
		}
		$db = Main::getInstance()->db;

		$stmt = $db->prepare("INSERT INTO players (name, level, xp, total_xp) VALUES (:name, :level, :xp, :total_xp)");
		$stmt->bindValue("name", $username, SQLITE3_TEXT);
		$stmt->bindValue("level", 0, SQLITE3_INTEGER);
		$stmt->bindValue("xp", 0, SQLITE3_INTEGER);
		$stmt->bindValue("total_xp", 0, SQLITE3_INTEGER);
		$stmt->execute();
		$stmt->close();

		return new LevelController($username);
	}

	/**
	 * @param $username
	 * @return bool
	 */
	public static function existsUser(string $username)
	{
		$db = Main::getInstance()->db;

		$stmt = $db->prepare("SELECT COUNT(*) AS amount FROM players WHERE name = :name");
		$stmt->bindValue("name", $username, SQLITE3_TEXT);
		$res = $stmt->execute()->fetchArray();
		$stmt->close();

		if ($res["amount"] != 0) {
			return true;
		}

		return false;
	}

	/**
	 * @param string $username
	 * @return false|int
	 */
	public static function getUserId(string $username)
	{
		$db = Main::getInstance()->db;

		if (!self::existsUser($username)) {
			return false;
		}
		$stmt = $db->prepare("SELECT id FROM players WHERE name = :name");
		$stmt->bindValue("name", $username, SQLITE3_TEXT);
		$res = $stmt->execute()->fetchArray();
		$stmt->close();

		$id = $res["id"];

		return $id;
	}

	/**
	 * @param int $userId
	 * @return string|null
	 */
	public static function getUsername(int $userId)
	{
		$db = Main::getInstance()->db;

		$stmt = $db->prepare("SELECT name FROM players WHERE id = :id");
		$stmt->bindValue("id", $userId, SQLITE3_TEXT);
		$res = $stmt->execute()->fetchArray();
		$stmt->close();

		if ($res["name"] and $res["name"] !== null) {
			return $res["name"];
		}

		return null;
	}
	
	/**
	 * @param false $sorted
	 * @param int $sortType
	 * @return array
	 */
	public static function getAllUsers($sorted = false, $sortType = SORT_DESC){
		$db = Main::getInstance()->db;
		
		$users = [];
		$stmt = $db->prepare("SELECT name FROM players");
		$exe = $stmt->execute();
		while ($row = $exe->fetchArray()){
			$name = $row["name"];
			$users[] = $name;
		}
		$stmt->close();

		if(!$sorted){
			return $users;
		}

		$totalxp = [];
		foreach($users as $key=>$user){
			$totalxp[$key] = self::getTotalXp($user);
		}
		array_multisort($totalxp, $sortType, $users);
		return $users;
	}

	//xp functions

	/**
	 * @param string $username
	 * @return false|int
	 */
	public static function getXp(string $username)
	{
		if (!self::existsUser($username)) {
			return false;
		}
		$db = Main::getInstance()->db;

		$stmt = $db->prepare("SELECT xp FROM players WHERE name = :name");
		$stmt->bindValue("name", $username, SQLITE3_TEXT);
		$res = $stmt->execute();
		$xp = 0;
		while ($row = $res->fetchArray()) {
			$xp = $row["xp"];
			break;
		}
		$stmt->close();

		return $xp;
	}

	/**
	 * @param string $username
	 * @param int $xpToAdd
	 * @return bool|null
	 */
	public static function addXp(string $username, int $xpToAdd)
	{
		if (!self::existsUser($username)) {
			return false;
		}

		if ($xpToAdd <= 0) {
			return null;
		}

		$totalXp = self::getTotalXp($username);
		$newTotalXp = $totalXp + $xpToAdd;

		self::setTotalXp($username, $newTotalXp);

		return true;
	}

	/**
	 * @param string $username
	 * @param int $xpToRemove
	 * @return bool|null
	 */
	public static function removeXp(string $username, int $xpToRemove)
	{
		if (!self::existsUser($username)) {
			return false;
		}

		$level = self::getLevel($username);

		$totalXp = self::getTotalXp($username);
		if ($totalXp < $xpToRemove or $xpToRemove <= 0) {
			return null;
		}

		$newTotalXp = $totalXp - $xpToRemove;

		self::setTotalXp($username, $newTotalXp);

		return true;
	}

	/**
	 * @param int $level
	 * @return int
	 */
	public static function getXpNextLevel(int $level)
	{
		if ($level < 0) {
			return 0;
		}
		return (5 * pow($level, 2) + (50 * $level) + 100);
	}

	//totalxp functions

	/**
	 * @param string $username
	 * @return false|int
	 */
	public static function getTotalXp(string $username)
	{
		$db = Main::getInstance()->db;

		if (!self::existsUser($username)) {
			return false;
		}
		$stmt = $db->prepare("SELECT total_xp FROM players WHERE name = :name");
		$stmt->bindValue("name", $username, SQLITE3_TEXT);
		$res = $stmt->execute();
		$total = 0;
		while ($row = $res->fetchArray()) {
			$total = $row["total_xp"];
			break;
		}
		$stmt->close();

		return $total;

	}

	/**
	 * @param string $username
	 * @param int $newTotalXp
	 * @return bool
	 */
	public static function setTotalXp(string $username, int $newTotalXp)
	{
		$main = Main::getInstance();
		$db = $main->db;
		if (!self::existsUser($username)) {
			return false;
		}

		$newXp = self::getXpByTotalXp($newTotalXp);
		$newLevel = self::getLevelByTotalXp($newTotalXp);
		$oldLevel = self::getLevel($username);

		if($oldLevel != $newLevel){
			$player = $main->getServer()->getPlayer($username);

			$main->getServer()->getPluginManager()->callEvent(new LevelChangeEvent($main, $player, $oldLevel, $newLevel));

		}

		$stmt = $db->prepare("UPDATE players SET level = :level, xp = :xp, total_xp = :total_xp WHERE name = :name");
		$stmt->bindValue("level", $newLevel, SQLITE3_INTEGER);
		$stmt->bindValue("xp", $newXp, SQLITE3_INTEGER);
		$stmt->bindValue("total_xp", $newTotalXp, SQLITE3_INTEGER);
		$stmt->bindValue("name", $username, SQLITE3_TEXT);
		$stmt->execute();
		$stmt->close();

		return true;
	}

	/**
	 * @param int $xp
	 * @return int
	 */
	public static function getLevelByTotalXp(int $totalXp)
	{
		$level = 0;
		$levelUpXp = self::getXpNextLevel($level);
		while ($totalXp >= $levelUpXp) {
			$totalXp = $totalXp - $levelUpXp;
			$level++;
			$levelUpXp = self::getXpNextLevel($level);
		}

		return $level;
	}

	/**
	 * Get the xp from totalxp
	 *
	 * @param int $totalXp
	 * @return int
	 */
	public static function getXpByTotalXp(int $totalXp)
	{
		$level = self::getLevelByTotalXp($totalXp);
		$xpUsed = 0;
		for($i=0;$i<$level;$i++){
			$xpUsed += self::getXpNextLevel($i);
		}
		$xp = $totalXp - $xpUsed;

		return $xp;
	}

	//level functions

	/**
	 * @param string $username
	 * @return false|int
	 */
	public static function getLevel(string $username)
	{
		if (!self::existsUser($username)) {
			return false;
		}
		$db = Main::getInstance()->db;

		$stmt = $db->prepare("SELECT level FROM players WHERE name = :name");
		$stmt->bindValue("name", $username, SQLITE3_TEXT);
		$res = $stmt->execute();
		$level = 0;
		while ($row = $res->fetchArray()) {
			$level = $row["level"];
			break;
		}
		$stmt->close();

		return $level;
	}

	/**
	 * @param string $username
	 * @param int $newLevel
	 * @return bool|null
	 */
	public static function setLevel(string $username, int $newLevel)
	{
		$db = Main::getInstance()->db;

		if (!self::existsUser($username)) {
			return false;
		}

		if ($newLevel < 0) {
			return null;
		}
		$newXp = 0;
		$newTotalXp = self::getTotalXpByLevel($newLevel);
		self::setTotalXp($username, $newTotalXp);

		return true;
	}

	/**
	 * @param int $level
	 * @param int $xp
	 * @return int
	 */
	public static function getTotalXpByLevel(int $level, int $xp = 0){
		$totalXp = 0;
		$lvl = 0;
		while($lvl < $level){
			$lvl ++;
			$totalXp += self::getXpNextLevel($lvl-1);
		}
		$totalXp += $xp;

		return $totalXp;
	}
}
