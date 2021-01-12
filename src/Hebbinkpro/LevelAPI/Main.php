<?php

namespace Hebbinkpro\LevelAPI;

use Hebbinkpro\LevelAPI\events\LevelChangeEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerChatEvent;
use SQLite3;

use _64FF00\PureChat\PureChat;

class Main extends PluginBase implements Listener
{

	public static $instance;
	public $db;
	public $config;
	public $PureChat;

	public static function getInstance()
	{
		return self::$instance;
	}

	public function onEnable()
	{
		self::$instance = $this;
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->db = new SQLite3($this->getDataFolder() . "levels.db");
		$this->db->query("CREATE TABLE IF NOT EXISTS players (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, level INTEGER, xp INTEGER, total_xp INTEGER)");

		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
			"#Use {level} for level of the user",
			"suffix" => "Level {level}"
		]);

		$this->PureChat = $this->getServer()->getPluginManager()->getPlugin("PureChat");

	}

	public function onLevelChange(LevelChangeEvent $e){
		$player = $e->getPlayer();
		if ($this->PureChat instanceof PureChat and $player instanceof Player) {
			$newSuffix = $this->getCfgSuffix($player);
			$suffix = $this->PureChat->getSuffix($player);
			if ($suffix === null or $suffix != $newSuffix) {
				$this->PureChat->setSuffix($newSuffix, $player);
			}
		}
	}

	public function onJoin(PlayerJoinEvent $e)
	{
		$player = $e->getPlayer();

		if (!LevelController::existsUser($player->getName())) {
			$newUser = LevelController::addUser($player->getName());
			$this->getLogger()->debug("Added " . $newUser->name . " to the database");
		}

		if ($this->PureChat instanceof PureChat) {
			$newSuffix = $this->getCfgSuffix($player);
			$suffix = $this->PureChat->getSuffix($player);
			if ($suffix === null or $suffix != $newSuffix) {
				$this->PureChat->setSuffix($newSuffix, $player);
			}
		}
	}

	public function onChat(PlayerChatEvent $e)
	{
		$player = $e->getPlayer();
		$message = $e->getMessage();
		$pos = strpos($message, "/");
		if ($pos === 1) {
			return true;
		}

		if ($this->PureChat instanceof PureChat) {
			$newSuffix = $this->getCfgSuffix($player);
			$suffix = $this->PureChat->getSuffix($player);
			if ($suffix === null or $suffix != $newSuffix) {
				$this->PureChat->setSuffix($newSuffix, $player);
			}
		}
	}

	public function getCfgSuffix(Player $user)
	{
		$format = $this->config->get("suffix");
		$level = LevelController::getLevel($user->getName());
		$text = str_replace("{level}", strval($level), $format);
		return $text;
	}

}