<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pocketmine\item\Consumable;
use pocketmine\item\ItemFactory;
use pocketmine\utils\Binary;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\utils\ProtocolUtils;

class ActorEventPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ACTOR_EVENT_PACKET;

	public const JUMP = 1;
	public const HURT_ANIMATION = 2;
	public const DEATH_ANIMATION = 3;
	public const ARM_SWING = 4;
	public const STOP_ATTACK = 5;
	public const TAME_FAIL = 6;
	public const TAME_SUCCESS = 7;
	public const SHAKE_WET = 8;
	public const USE_ITEM = 9;
	public const EAT_GRASS_ANIMATION = 10;
	public const FISH_HOOK_BUBBLE = 11;
	public const FISH_HOOK_POSITION = 12;
	public const FISH_HOOK_HOOK = 13;
	public const FISH_HOOK_TEASE = 14;
	public const SQUID_INK_CLOUD = 15;
	public const ZOMBIE_VILLAGER_CURE = 16;

	public const RESPAWN = 18;
	public const IRON_GOLEM_OFFER_FLOWER = 19;
	public const IRON_GOLEM_WITHDRAW_FLOWER = 20;
	public const LOVE_PARTICLES = 21; //breeding
	public const VILLAGER_ANGRY = 22;
	public const VILLAGER_HAPPY = 23;
	public const WITCH_SPELL_PARTICLES = 24;
	public const FIREWORK_PARTICLES = 25;
	public const IN_LOVE_PARTICLES = 26;
	public const SILVERFISH_SPAWN_ANIMATION = 27;
	public const GUARDIAN_ATTACK = 28;
	public const WITCH_DRINK_POTION = 29;
	public const WITCH_THROW_POTION = 30;
	public const MINECART_TNT_PRIME_FUSE = 31;
	public const CREEPER_PRIME_FUSE = 32;
	public const AIR_SUPPLY_EXPIRED = 33;
	public const PLAYER_ADD_XP_LEVELS = 34;
	public const ELDER_GUARDIAN_CURSE = 35;
	public const AGENT_ARM_SWING = 36;
	public const ENDER_DRAGON_DEATH = 37;
	public const DUST_PARTICLES = 38; //not sure what this is
	public const ARROW_SHAKE = 39;

	public const EATING_ITEM = 57;

	public const BABY_ANIMAL_FEED = 60; //green particles, like bonemeal on crops
	public const DEATH_SMOKE_CLOUD = 61;
	public const COMPLETE_TRADE = 62;
	public const REMOVE_LEASH = 63; //data 1 = cut leash

	public const CONSUME_TOTEM = 65;
	public const PLAYER_CHECK_TREASURE_HUNTER_ACHIEVEMENT = 66; //mojang...
	public const ENTITY_SPAWN = 67; //used for MinecraftEventing stuff, not needed
	public const DRAGON_PUKE = 68; //they call this puke particles
	public const ITEM_ENTITY_MERGE = 69;
	public const START_SWIM = 70;
	public const BALLOON_POP = 71;
	public const TREASURE_HUNT = 72;
	public const AGENT_SUMMON = 73;
	public const CHARGED_CROSSBOW = 74;
	public const FALL = 75;

	//TODO: add more events

	/** @var int */
	public $entityRuntimeId;
	/** @var int */
	public $event;
	/** @var int */
	public $data = 0;
	/** @var int */
	public $entityProtocol = -1;

	protected function decodePayload(){
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->event = (\ord($this->get(1)));
		$this->data = $this->getVarInt();
	}

	protected function encodePayload(){
		$this->putEntityRuntimeId($this->entityRuntimeId);
		($this->buffer .= \chr($this->event));

		
		// Fixing eat event data
		if($this->event === self::EATING_ITEM && ProtocolUtils::convertToEatingData($this->entityProtocol) !== ProtocolUtils::convertToEatingData($this->protocol)) {
			// TODO: improve this!
			$protocol_419_to_407 = [
				35454976 => 27787264, 18087936 => 23986176, 17956864 => 23855104, 
				17235968 => 20971520, 18939904 => 27000832, 17104896 => 19464192,
				17039360 => 18481152, 17563648 => 22937600, 17629184 => 30343168,
				18743296 => 30081024, 19005440 => 27066368, 17760256 => 23396352,
				18612224 => 26214400, 18677760 => 29949952, 18350080 => 25690112,
				18481152 => 25821184, 18284544 => 25624576, 18415616 => 25755648,
				18546688 => 25952256, 16842752 => 17039360, 16908288 => 21102592,
				16973824 => 30539776, 17825792 => 23592960, 17432576 => 30212096,
				17498112 => 30277632, 18022400 => 23920640, 17170432 => 20905984,
				17891328 => 23789568, 35389440 => 27721728, 18874368 => 26935296,
				17301504 => 22872064, 17367040 => 30146560, 18219008 => 24576000,
				18153472 => 24051712
			];
			if($this->entityProtocol >= ProtocolInfo::PROTOCOL_419) {
				$this->data = $protocol_419_to_407[$this->data] ?? $this->data;
			} else {
				$data = array_search($this->data, $protocol_419_to_407, true);
				$this->data = ($data === false) ? $this->data : $data;
			}
		}
		$this->putVarInt($this->data);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleActorEvent($this);
	}
}
