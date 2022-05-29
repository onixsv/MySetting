<?php
declare(strict_types=1);

namespace MySetting;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\ServerSettingsRequestPacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

class MySetting extends PluginBase implements Listener{
	use SingletonTrait;

	/** @var Setting[] */
	protected array $settings = [];

	/** @var Config */
	protected Config $config;

	protected array $db = [];

	protected function onLoad() : void{
		self::setInstance($this);
	}

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->config = new Config($this->getDataFolder() . "settings.yml", Config::YAML, []);
		$this->db = $this->config->getAll();
	}

	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		if(!isset($this->db[$player->getName()])){
			$data = Setting::SETTINGS_DEFAULT;
		}else{
			$data = $this->db[$player->getName()];
		}
		$setting = new Setting($player, $data);
		$this->settings[$player->getName()] = $setting;
	}

	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		if(($setting = $this->getSetting($player)) instanceof Setting){
			$data = $setting->getSettings();
			$this->db[$player->getName()] = $data;
			unset($this->settings[$player->getName()]);
		}
	}

	protected function onDisable() : void{
		foreach($this->settings as $name => $setting){
			$this->db[$name] = $setting->getSettings();
		}
		$this->config->setAll($this->db);
		$this->config->save();
	}

	public function onDataPacketReceived(DataPacketReceiveEvent $event) : void{
		$player = $event->getOrigin()->getPlayer();
		$packet = $event->getPacket();
		if($packet instanceof ServerSettingsRequestPacket){
			$event->cancel();
			if(($setting = $this->getSetting($player)) instanceof Setting){
				$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $setting) : void{
					$player->getNetworkSession()->sendDataPacket($setting->getFormPacket());
				}), 20);
			}
		}elseif($packet instanceof ModalFormResponsePacket){
			if($packet->formId === Setting::SETTING_UI_ID){
				$data = json_decode($packet->formData, true);
				if(is_array($data) && count($data) === count(Setting::SETTINGS_DEFAULT)){
					if(($setting = $this->getSetting($player)) instanceof Setting){
						$setting->handleForm($data);
					}
				}
			}
		}
	}

	public function onPlayerDropItem(PlayerDropItemEvent $event) : void{
		$player = $event->getPlayer();
		if(($setting = $this->getSetting($player)) instanceof Setting){
			if(!$setting->getSetting(Setting::SETTINGS_DROP)){
				$event->cancel();
				$player->sendPopup("§f현재 §d설정§f에 의해 §d아이템§f을 버리지 못합니다.");
			}
		}
	}

	public function getSetting(Player $player) : ?Setting{
		return $this->settings[$player->getName()] ?? null;
	}
}