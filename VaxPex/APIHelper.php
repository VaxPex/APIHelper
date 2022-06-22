<?php

declare(strict_types=1);

namespace VaxPex;

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\Vine;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\player\Player;
use pocketmine\world\World;

class APIHelper
{

	/**
	 * Helper function which creates minimal NBT needed to spawn an entity.
	 */
	public static function makeEntityNBT(Vector3 $position, ?Vector3 $motion = null, float $yaw = 0.0, float $pitch = 0.0): CompoundTag{
		return CompoundTag::create()
			->setTag("Pos", new ListTag([
				new DoubleTag($position->x),
				new DoubleTag($position->y),
				new DoubleTag($position->z)
			]))
			->setTag("Motion", new ListTag([
				new DoubleTag($motion !== null ? $motion->x : 0),
				new DoubleTag($motion !== null ? $motion->y : 0),
				new DoubleTag($motion !== null ? $motion->z : 0)
			]))
			->setTag("Rotation", new ListTag([
				new FloatTag($yaw),
				new FloatTag($pitch)
			]));
	}

	public static function getGenericFlag(Entity $entity, int $flagId, bool $is = false) {
		if($is === true){
			return $entity->getNetworkProperties()->getAll()[$flagId] !== null && $entity->getNetworkProperties()->getAll()[$flagId]->equals(new ByteMetadataProperty(1));
		}
		return $entity->getNetworkProperties()->getAll()[$flagId];
	}

	public static function broadcastActorEvent(Entity $entity, int $eventId, ?int $eventData = null, ?array $players = null): void{
		self::broadcastPacket($players ?? $entity->getViewers(), ActorEventPacket::create($entity->getId(), $eventId, $eventData ?? 0));
	}

	/**
	 * @param Player[] $players
	 * @param ClientboundPacket $pk
	 * @return void
	 */
	public static function broadcastPacket(array $players, ClientboundPacket $pk){
		foreach($players as $player){
			$player->getNetworkSession()->sendDataPacket($pk);
		}
	}

	public static function broadcastLevelSoundEvent(World $world, Vector3 $pos, int $soundId, int $extraData = -1, string $entityTypeId = ':', bool $isBabyMob = false, bool $disableRelativeVolume = false): void{
		$pk = new LevelSoundEventPacket();
		$pk->sound = $soundId;
		$pk->extraData = $extraData;
		$pk->entityType = $entityTypeId;
		$pk->isBabyMob = $isBabyMob;
		$pk->disableRelativeVolume = $disableRelativeVolume;
		$pk->position = $pos->asVector3();
		$world->broadcastPacketToViewers($pos, $pk);
	}

	public static function canPassThroughBlock(Block $block): bool{
		if($block instanceof Air){
			return true;
		}
		if($block instanceof Vine){
			return true;
		}
		return false;
	}
}
