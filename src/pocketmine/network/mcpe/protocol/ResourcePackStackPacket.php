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
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\resourcepacks\ResourcePack;
use function count;

class ResourcePackStackPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACK_STACK_PACKET;

	/** @var bool */
	public $mustAccept = false;

	/** @var ResourcePack[] */
	public $behaviorPackStack = [];
	/** @var ResourcePack[] */
	public $resourcePackStack = [];

	/** @var string */
	public $baseGameVersion = ProtocolInfo::MINECRAFT_VERSION_NETWORK;

	/** @var Experiments */
	public $experiments;

	protected function decodePayload(){
		$this->mustAccept = (($this->get(1) !== "\x00"));
		$behaviorPackCount = $this->getUnsignedVarInt();
		while($behaviorPackCount-- > 0){
			$this->getString();
			$this->getString();
			$this->getString();
		}

		$resourcePackCount = $this->getUnsignedVarInt();
		while($resourcePackCount-- > 0){
			$this->getString();
			$this->getString();
			$this->getString();
		}

		if($this->protocol >= ProtocolInfo::PROTOCOL_419) {
			$this->baseGameVersion = $this->getString();
			$this->experiments = Experiments::read($this);
		} else {
			$this->experiments = new Experiments(['' => ($this->get(1) !== "\x00")], false);
			$this->baseGameVersion = $this->getString();
		}
	}

	protected function encodePayload(){
		($this->buffer .= ($this->mustAccept ? "\x01" : "\x00"));

		$this->putUnsignedVarInt(count($this->behaviorPackStack));
		foreach($this->behaviorPackStack as $entry){
			$this->putString($entry->getPackId());
			$this->putString($entry->getPackVersion());
			$this->putString(""); //TODO: subpack name
		}

		$this->putUnsignedVarInt(count($this->resourcePackStack));
		foreach($this->resourcePackStack as $entry){
			$this->putString($entry->getPackId());
			$this->putString($entry->getPackVersion());
			$this->putString(""); //TODO: subpack name
		}

		if($this->protocol >= ProtocolInfo::PROTOCOL_419) {
			$this->putString($this->baseGameVersion);
			$this->experiments->write($this);
		} else {
			$enabled = \in_array(true, $this->experiments->getExperiments());

			$this->putBool($enabled);
			$this->putString($this->baseGameVersion);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleResourcePackStack($this);
	}
}
