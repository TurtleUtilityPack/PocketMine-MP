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
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\utils\UUID;
use function count;

class AddPlayerPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ADD_PLAYER_PACKET;

	/** @var UUID */
	public $uuid;
	/** @var string */
	public $username;
	/** @var int|null */
	public $entityUniqueId = null; //TODO
	/** @var int */
	public $entityRuntimeId;
	/** @var string */
	public $platformChatId = "";
	/** @var Vector3 */
	public $position;
	/** @var Vector3|null */
	public $motion;
	/** @var float */
	public $pitch = 0.0;
	/** @var float */
	public $yaw = 0.0;
	/** @var float|null */
	public $headYaw = null; //TODO
	/** @var ItemStackWrapper */
	public $item;
	/**
	 * @var mixed[][]
	 * @phpstan-var array<int, array{0: int, 1: mixed}>
	 */
	public $metadata = [];

	//TODO: adventure settings stuff
	/** @var int */
	public $uvarint1 = 0;
	/** @var int */
	public $uvarint2 = 0;
	/** @var int */
	public $uvarint3 = 0;
	/** @var int */
	public $uvarint4 = 0;
	/** @var int */
	public $uvarint5 = 0;

	/** @var int */
	public $long1 = 0;

	/** @var EntityLink[] */
	public $links = [];

	/** @var string */
	public $deviceId = ""; //TODO: fill player's device ID (???)
	/** @var int */
	public $buildPlatform = DeviceOS::UNKNOWN;

	protected function decodePayload(){
		$this->uuid = $this->getUUID();
		$this->username = $this->getString();
		$this->entityUniqueId = $this->getEntityUniqueId();
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->platformChatId = $this->getString();
		$this->position = $this->getVector3();
		$this->motion = $this->getVector3();
		$this->pitch = ((\unpack("g", $this->get(4))[1]));
		$this->yaw = ((\unpack("g", $this->get(4))[1]));
		$this->headYaw = ((\unpack("g", $this->get(4))[1]));

		if($this->protocol >= ProtocolInfo::PROTOCOL_431) {
			$this->item = ItemStackWrapper::read($this, $this->protocol);
		} else {
			$this->item = $this->getItemStack();
		}
		$this->metadata = $this->getEntityMetadata();

		$this->uvarint1 = $this->getUnsignedVarInt();
		$this->uvarint2 = $this->getUnsignedVarInt();
		$this->uvarint3 = $this->getUnsignedVarInt();
		$this->uvarint4 = $this->getUnsignedVarInt();
		$this->uvarint5 = $this->getUnsignedVarInt();

		$this->long1 = (Binary::readLLong($this->get(8)));

		$linkCount = $this->getUnsignedVarInt();
		for($i = 0; $i < $linkCount; ++$i){
			$this->links[$i] = $this->getEntityLink();
		}

		$this->deviceId = $this->getString();
		$this->buildPlatform = ((\unpack("V", $this->get(4))[1] << 32 >> 32));
	}

	protected function encodePayload(){
		$this->putUUID($this->uuid);
		$this->putString($this->username);
		$this->putEntityUniqueId($this->entityUniqueId ?? $this->entityRuntimeId);
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putString($this->platformChatId);
		$this->putVector3($this->position);
		$this->putVector3Nullable($this->motion);
		($this->buffer .= (\pack("g", $this->pitch)));
		($this->buffer .= (\pack("g", $this->yaw)));
		($this->buffer .= (\pack("g", $this->headYaw ?? $this->yaw)));

		if($this->protocol >= ProtocolInfo::PROTOCOL_431) {
			$this->item->write($this);
		} else {
			$this->putItemStack($this->item->getItemStack());
		}
		$this->putEntityMetadata($this->metadata);

		$this->putUnsignedVarInt($this->uvarint1);
		$this->putUnsignedVarInt($this->uvarint2);
		$this->putUnsignedVarInt($this->uvarint3);
		$this->putUnsignedVarInt($this->uvarint4);
		$this->putUnsignedVarInt($this->uvarint5);

		($this->buffer .= (\pack("VV", $this->long1 & 0xFFFFFFFF, $this->long1 >> 32)));

		$this->putUnsignedVarInt(count($this->links));
		foreach($this->links as $link){
			$this->putEntityLink($link);
		}

		$this->putString($this->deviceId);
		($this->buffer .= (\pack("V", $this->buildPlatform)));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAddPlayer($this);
	}
}
