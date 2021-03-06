<?php

namespace Eard\Enemys;

use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\Location;
use pocketmine\level\Explosion;
use pocketmine\level\MovingObjectPosition;
use pocketmine\level\format\FullChunk;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\enchantment\Enchantment;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteTag;

use pocketmine\entity\Entity;
use pocketmine\entity\Human;

/**各エネミーに継承させるためのクラス
 */

class Humanoid extends Human{

	protected $gravity = 0.14;
	public $attackingTick = 0;
	/**
	 * 貫通できるブロックかを返す
	 *
	 * @param int $blockId
	 */
	public static function canThrough($blockId){
		switch($blockId){
			case 0:
			case 8:
			case 9:
			case 10:
			case 11:
			case 31:
			case 32:			
			case 38:			
			case 37:			
			case 50:
			case 52:
			case 65:
			case 101:
			case 175:
			case 208:
				return true;
			break;
			default:
				return false;
			break;
		}
	}

	public function getDrops(){
		$drops = [];
		if($this->lastDamageCause instanceof EntityDamageByEntityEvent and $this->lastDamageCause->getDamager() instanceof Player){
			$all_drops = static::getAllDrops();
			foreach($all_drops as $key => $value){
				list($id, $data, $amount, $percent) = $value;
				if(mt_rand(0, 1000) < $percent*10){
					$drops[] = Item::get($id, $data, $amount);
				}
			}
		}
		return $drops;
	}

	//ちゃんと動いてもらうための補助関数(PMMP側から呼び出される)
	public function onUpdate($tick){
		if($this instanceof Human){
			if($this->attackingTick > 0){
				$this->attackingTick--;
			}
			if(!$this->isAlive() and $this->hasSpawned){
				++$this->deadTicks;
				if($this->deadTicks >= 20){
					$this->despawnFromAll();
				}
				return true;
			}
			if($this->isAlive()){

				$this->motionY -= $this->gravity;

				$this->move($this->motionX, $this->motionY, $this->motionZ);

				$friction = 1 - $this->drag;

				if($this->onGround and (abs($this->motionX) > 0.00001 or abs($this->motionZ) > 0.00001)){
					$friction = $this->getLevel()->getBlock($this->temporalVector->setComponents((int) floor($this->x), (int) floor($this->y - 1), (int) floor($this->z) - 1))->getFrictionFactor() * $friction;
				}

				$this->motionX *= $friction;
				$this->motionY *= 1 - $this->drag;
				$this->motionZ *= $friction;

				if($this->onGround){
					$this->motionY *= -0.5;
				}

				if(!self::canThrough($this->getLevel()->getBlockIdAt($this->x, $this->y-1.65, $this->z))){
					$this->motionY = $this->gravity;
				}

				$this->updateMovement();
			}
		}
		parent::entityBaseTick();
		$grandParent = get_parent_class(get_parent_class($this));
		return $grandParent::onUpdate($tick);
	}

	public function attack($damage, EntityDamageEvent $source){
		if($source->getCause() === EntityDamageEvent::CAUSE_FALL){
			$source->setCancelled(true);
		}
		parent::attack($damage, $source);
	}
}