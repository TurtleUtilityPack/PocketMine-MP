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

class MobEquipmentPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::MOB_EQUIPMENT_PACKET;

	/** @var int */
	public $entityRuntimeId;
	/** @var ItemStackWrapper */
	public $item;
	/** @var int */
	public $inventorySlot;
	/** @var int */
	public $hotbarSlot;
	/** @var int */
	public $windowId = 0;

	protected function decodePayload(){
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		if($this->protocol >= ProtocolInfo::PROTOCOL_431) {
			$this->item = ItemStackWrapper::read($this, $this->protocol);
		} else {
			$this->item = ItemStackWrapper::legacy($this->getItemStack());
		}
		$this->inventorySlot = (\ord($this->get(1)));
		$this->hotbarSlot = (\ord($this->get(1)));
		$this->windowId = (\ord($this->get(1)));
	}

	protected function encodePayload(){
		$this->putEntityRuntimeId($this->entityRuntimeId);
		if($this->protocol >= ProtocolInfo::PROTOCOL_431) {
			$this->item->write($this);
		} else {
			$this->putItemStack($this->item->getItemStack());
		}
		($this->buffer .= \chr($this->inventorySlot));
		($this->buffer .= \chr($this->hotbarSlot));
		($this->buffer .= \chr($this->windowId));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleMobEquipment($this);
	}
}
