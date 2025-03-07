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

use pocketmine\network\mcpe\NetworkSession;

class SetSpawnPositionPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SET_SPAWN_POSITION_PACKET;

	public const TYPE_PLAYER_SPAWN = 0;
	public const TYPE_WORLD_SPAWN = 1;

	/** @var int */
	public $spawnType;
	/** @var int */
	public $x;
	/** @var int */
	public $y;
	/** @var int */
	public $z;
	/** @var int */
	public $dimension;
	/** @var int */
	public $x2;
	/** @var int */
	public $y2;
	/** @var int */
	public $z2;
	/** @var bool */
	public $spawnForced = false;

	protected function decodePayload(){
		$this->spawnType = $this->getVarInt();
		$this->getBlockPosition($this->x, $this->y, $this->z);
		if($this->protocol >= ProtocolInfo::PROTOCOL_407) {
			$this->dimension = $this->getVarInt();
			$this->getBlockPosition($this->x2, $this->y2, $this->z2);
		} else {
			$this->dimension = 0;
			$this->x2 = $this->x;
			$this->y2 = $this->y;
			$this->z2 = $this->z;
			$this->spawnForced = ($this->get(1) !== "\x00");
		}
	}

	protected function encodePayload() {
		$this->putVarInt($this->spawnType);
		$this->putBlockPosition($this->x, $this->y, $this->z);

		if($this->protocol >= ProtocolInfo::PROTOCOL_407) {
			$this->putVarInt($this->dimension);
			$this->putBlockPosition($this->x2, $this->y2, $this->z2);
		} else {
			($this->buffer .= ($this->spawnForced ? "\x01" : "\x00"));
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleSetSpawnPosition($this);
	}
	
}