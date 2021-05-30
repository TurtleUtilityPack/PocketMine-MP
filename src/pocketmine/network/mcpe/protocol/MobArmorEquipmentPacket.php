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

use pocketmine\utils\Binary;

use pocketmine\item\Item;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

class MobArmorEquipmentPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET;

	/** @var int */
	public $entityRuntimeId;

	//this intentionally doesn't use an array because we don't want any implicit dependencies on internal order

	/** @var ItemStackWrapper */
	public $head;
	/** @var ItemStackWrapper */
	public $chest;
	/** @var ItemStackWrapper */
	public $legs;
	/** @var ItemStackWrapper */
	public $feet;

	protected function decodePayload(){
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		if($this->protocol >= ProtocolInfo::PROTOCOL_431) {
			$this->head  = ItemStackWrapper::read($this, $this->protocol);
			$this->chest = ItemStackWrapper::read($this, $this->protocol);
			$this->legs  = ItemStackWrapper::read($this, $this->protocol);
			$this->feet  = ItemStackWrapper::read($this, $this->protocol);
		} else {
			$this->head  = ItemStackWrapper::legacy($this->getItemStack());
			$this->chest = ItemStackWrapper::legacy($this->getItemStack());
			$this->legs  = ItemStackWrapper::legacy($this->getItemStack());
			$this->feet  = ItemStackWrapper::legacy($this->getItemStack());
		}
	}

	protected function encodePayload(){
		$this->putEntityRuntimeId($this->entityRuntimeId);
		if($this->protocol >= ProtocolInfo::PROTOCOL_431) {
			$this->head->write($this);
			$this->chest->write($this);
			$this->legs->write($this);
			$this->feet->write($this);
		} else {
			$this->putItemStack($this->head->getItemStack());
			$this->putItemStack($this->chest->getItemStack());
			$this->putItemStack($this->legs->getItemStack());
			$this->putItemStack($this->feet->getItemStack());
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleMobArmorEquipment($this);
	}
}
