<?php
declare(strict_types=1);

namespace MySetting;

use pocketmine\network\mcpe\protocol\ServerSettingsResponsePacket;
use pocketmine\player\Player;

class Setting{

	public const SETTING_UI_ID = 991321;

	/** Constants */
	public const SETTINGS_DROP = "아이템 드롭";

	public const SETTINGS_ALERT_CLEANER = "아이템 청소 알림";

	public const SETTINGS_SIT_CHAIR = "의자 앉기";

	public const SETTINGS_DEFAULT = [
		self::SETTINGS_DROP => true,
		self::SETTINGS_ALERT_CLEANER => false,
		self::SETTINGS_SIT_CHAIR => true
	];

	/** Start Setting object */

	/** @var Player */
	protected Player $player;

	protected array $settings = self::SETTINGS_DEFAULT;

	public function __construct(Player $player, array $settings = self::SETTINGS_DEFAULT){
		$this->player = $player;
		$this->settings = $settings;
		$this->fix();
	}

	private function fix() : void{
		foreach(self::SETTINGS_DEFAULT as $key => $value){
			if(!isset($this->settings[$key])){
				$this->settings[$key] = $value;
			}
		}
	}

	public function getSettings() : array{
		return $this->settings;
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function getSetting(string $setting) : bool{
		return $this->settings[$setting];
	}

	public function setSetting(string $setting, bool $value) : void{
		$this->settings[$setting] = $value;
	}

	public function getFormPacket() : ServerSettingsResponsePacket{
		$encode = [
			"type" => "custom_form",
			"title" => "오닉스 서버 설정",
			"content" => []
		];
		foreach($this->settings as $name => $setting){
			$encode["content"][] = [
				"type" => "toggle",
				"default" => $setting,
				"text" => $name
			];
		}
		$pk = new ServerSettingsResponsePacket();
		$pk->formId = self::SETTING_UI_ID;
		$pk->formData = json_encode($encode);
		return $pk;
	}

	public function handleForm(array $data) : void{
		$names = array_keys($this->settings);
		foreach($data as $index => $value){
			$this->settings[$names[$index]] = $value;
		}
	}
}