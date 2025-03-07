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

class ContainerClosePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CONTAINER_CLOSE_PACKET;

	/** @var int */
	public $windowId;
	/** @var bool */
	public $server = false;

	protected function decodePayload(){
		$this->windowId = (\ord($this->get(1)));
		
		if($this->protocol >= ProtocolInfo::PROTOCOL_419) {
			$this->server = (($this->get(1) !== "\x00"));
		}
	}

	protected function encodePayload(){
		($this->buffer .= \chr($this->windowId));

		if($this->protocol >= ProtocolInfo::PROTOCOL_419) {
			($this->buffer .= ($this->server ? "\x01" : "\x00"));
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleContainerClose($this);
	}
}
