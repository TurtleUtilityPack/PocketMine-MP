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
use pocketmine\utils\UUID;
use function count;

class CraftingEventPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CRAFTING_EVENT_PACKET;

	/** @var int */
	public $windowId;
	/** @var int */
	public $type;
	/** @var UUID */
	public $id;
	/** @var ItemStackWrapper[] */
	public $input = [];
	/** @var ItemStackWrapper[] */
	public $output = [];

	public function clean(){
		$this->input = [];
		$this->output = [];
		return parent::clean();
	}

	protected function decodePayload(){
		$this->windowId = (\ord($this->get(1)));
		$this->type = $this->getVarInt();
		$this->id = $this->getUUID();

		$size = $this->getUnsignedVarInt();
		if($this->protocol >= ProtocolInfo::PROTOCOL_431) {
			for($i = 0; $i < $size and $i < 128; ++$i) $this->input[] = ItemStackWrapper::read($this, $this->protocol);
		} else {
			for($i = 0; $i < $size and $i < 128; ++$i) $this->input[] = ItemStackWrapper::legacy($this->getItemStack());
		}

		$size = $this->getUnsignedVarInt();
		if($this->protocol >= ProtocolInfo::PROTOCOL_431) {
			for($i = 0; $i < $size and $i < 128; ++$i) $this->output[] = ItemStackWrapper::read($this, $this->protocol);
		} else {
			for($i = 0; $i < $size and $i < 128; ++$i) $this->output[] = ItemStackWrapper::legacy($this->getItemStack());
		}
	}

	protected function encodePayload(){
		($this->buffer .= \chr($this->windowId));
		$this->putVarInt($this->type);
		$this->putUUID($this->id);

		$this->putUnsignedVarInt(count($this->input));
		if($this->protocol >= ProtocolInfo::PROTOCOL_431) {
			foreach($this->input as $wrapper) $wrapper->write($this);
		} else {
			foreach($this->input as $wrapper) $this->putItemStack($wrapper->getItemStack());
		}

		$this->putUnsignedVarInt(count($this->output));
		if($this->protocol >= ProtocolInfo::PROTOCOL_431) {
			foreach($this->output as $wrapper) $wrapper->write($this);
		} else {
			foreach($this->output as $wrapper) $this->putItemStack($wrapper->getItemStack());
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCraftingEvent($this);
	}
}
