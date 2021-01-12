<?php


namespace Hebbinkpro\LevelAPI\events;

use pocketmine\event\Event;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;
use Hebbinkpro\LevelAPI\Main;
use pocketmine\plugin\Plugin;

class LevelChangeEvent extends PluginEvent
{

	public const TYPE_LEVEL_UP = 0;
	public const TYPE_LEVEL_DOWN = 1;
	public const TYPE_LEVEL_SAME = 2;

	protected $player;
	protected $oldLevel;
	protected $newLevel;
	protected $type;

	public function __construct(Plugin $plugin, $player, int $oldLevel, int $newLevel){
		parent::__construct($plugin);

		$this->player = $player;
		$this->oldLevel = $oldLevel;
		$this->newLevel = $newLevel;

		if($this->oldLevel < $this->newLevel){
			$this->type = self::TYPE_LEVEL_UP;
		} elseif($this->oldLevel > $this->newLevel){
			$this->type = self::TYPE_LEVEL_DOWN;
		} else{
			$this->type = self::TYPE_LEVEL_SAME;
		}
	}

	public function getPlayer(){
		return $this->player;
	}

	public function getOldLevel(){
		return $this->oldLevel;
	}

	public function getNewLevel(){
		return $this->newLevel;
	}

	public function getType(){
		return $this->type;
	}

}