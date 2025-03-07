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

use pocketmine\inventory\FurnaceRecipe;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\convert\item\ItemTranslator;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\PotionContainerChangeRecipe;
use pocketmine\network\mcpe\protocol\types\PotionTypeRecipe;
use function count;
use function str_repeat;

class CraftingDataPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CRAFTING_DATA_PACKET;

	public const ENTRY_SHAPELESS = 0;
	public const ENTRY_SHAPED = 1;
	public const ENTRY_FURNACE = 2;
	public const ENTRY_FURNACE_DATA = 3;
	public const ENTRY_MULTI = 4; //TODO
	public const ENTRY_SHULKER_BOX = 5; //TODO
	public const ENTRY_SHAPELESS_CHEMISTRY = 6; //TODO
	public const ENTRY_SHAPED_CHEMISTRY = 7; //TODO

	/** @var object[] */
	public $entries = [];
	/** @var PotionTypeRecipe[] */
	public $potionTypeRecipes = [];
	/** @var PotionContainerChangeRecipe[] */
	public $potionContainerRecipes = [];
	/** @var bool */
	public $cleanRecipes = false;

	/** @var mixed[][] */
	public $decodedEntries = [];

	public function clean(){
		$this->entries = [];
		$this->decodedEntries = [];
		return parent::clean();
	}

	protected function decodePayload(){
		$this->decodedEntries = [];
		$recipeCount = $this->getUnsignedVarInt();
		for($i = 0; $i < $recipeCount; ++$i){
			$entry = [];
			$entry["type"] = $recipeType = $this->getVarInt();

			switch($recipeType){
				case self::ENTRY_SHAPELESS:
				case self::ENTRY_SHULKER_BOX:
				case self::ENTRY_SHAPELESS_CHEMISTRY:
					$entry["recipe_id"] = $this->getString();
					$ingredientCount = $this->getUnsignedVarInt();
					/** @var Item */
					$entry["input"] = [];
					for($j = 0; $j < $ingredientCount; ++$j){
						$entry["input"][] = $in = $this->getRecipeIngredient();
						$in->setCount(1); //TODO HACK: they send a useless count field which breaks the PM crafting system because it isn't always 1
					}
					$resultCount = $this->getUnsignedVarInt();
					$entry["output"] = [];
					for($k = 0; $k < $resultCount; ++$k){
						$entry["output"][] = $this->getItemStack();
					}
					$entry["uuid"] = $this->getUUID()->toString();
					$entry["block"] = $this->getString();
					$entry["priority"] = $this->getVarInt();

					if($this->protocol >= ProtocolInfo::PROTOCOL_407) {
						$entry["net_id"] = $this->readGenericTypeNetworkId();
					}
					break;
				case self::ENTRY_SHAPED:
				case self::ENTRY_SHAPED_CHEMISTRY:
					$entry["recipe_id"] = $this->getString();
					$entry["width"] = $this->getVarInt();
					$entry["height"] = $this->getVarInt();
					$count = $entry["width"] * $entry["height"];
					$entry["input"] = [];
					for($j = 0; $j < $count; ++$j){
						$entry["input"][] = $in = $this->getRecipeIngredient();
						$in->setCount(1); //TODO HACK: they send a useless count field which breaks the PM crafting system
					}
					$resultCount = $this->getUnsignedVarInt();
					$entry["output"] = [];
					for($k = 0; $k < $resultCount; ++$k){
						$entry["output"][] = $this->getItemStack();
					}
					$entry["uuid"] = $this->getUUID()->toString();
					$entry["block"] = $this->getString();
					$entry["priority"] = $this->getVarInt();

					if($this->protocol >= ProtocolInfo::PROTOCOL_407) {
						$entry["net_id"] = $this->readGenericTypeNetworkId();
					}

					break;
				case self::ENTRY_FURNACE:
				case self::ENTRY_FURNACE_DATA:
					$inputIdNet = $this->getVarInt();

					if($this->protocol >= ProtocolInfo::PROTOCOL_419) {
						if($recipeType === self::ENTRY_FURNACE){
							[$inputId, $inputData] = ItemTranslator::getInstance()->fromNetworkIdWithWildcardHandling($inputIdNet, 0x7fff);
						}else{
							$inputMetaNet = $this->getVarInt();
							[$inputId, $inputData] = ItemTranslator::getInstance()->fromNetworkIdWithWildcardHandling($inputIdNet, $inputMetaNet);
						}
					} else {
						$inputId = $inputIdNet;
						$inputData = -1;

						if($recipeType === self::ENTRY_FURNACE_DATA) {

							$inputData = $this->getVarInt();
							if($inputData === 0x7fff) {
								$inputData = -1;
							}
						}
					}
					$entry["input"] = ItemFactory::get($inputId, $inputData);
					$entry["output"] = $out = $this->getItemStack();
					if($out->getDamage() === 0x7fff){
						$out->setDamage(0); //TODO HACK: some 1.12 furnace recipe outputs have wildcard damage values
					}
					$entry["block"] = $this->getString();

					break;
				case self::ENTRY_MULTI:
					$entry["uuid"] = $this->getUUID()->toString();

					if($this->protocol >= ProtocolInfo::PROTOCOL_407) {
						$entry["net_id"] = $this->readGenericTypeNetworkId();
					}
					break;
				default:
					throw new \UnexpectedValueException("Unhandled recipe type $recipeType!"); //do not continue attempting to decode
			}
			$this->decodedEntries[] = $entry;
		}
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$inputIdNet = $this->getVarInt();
			$inputMetaNet = $this->getVarInt();
			$ingredientIdNet = $this->getVarInt();
			$ingredientMetaNet = $this->getVarInt();
			$outputIdNet = $this->getVarInt();
			$outputMetaNet = $this->getVarInt();

			if($this->protocol >= ProtocolInfo::PROTOCOL_419) {
				[$input, $inputMeta] = ItemTranslator::getInstance()->fromNetworkId($inputIdNet, $inputMetaNet);
				[$ingredient, $ingredientMeta] = ItemTranslator::getInstance()->fromNetworkId($ingredientIdNet, $ingredientMetaNet);
				[$output, $outputMeta] = ItemTranslator::getInstance()->fromNetworkId($outputIdNet, $outputMetaNet);
			} else {
				[$input, $inputMeta]           = [$inputIdNet, $inputMetaNet];
				[$ingredient, $ingredientMeta] = [$ingredientIdNet, $ingredientMetaNet];
				[$output, $outputMeta]         = [$outputIdNet, $outputMetaNet];
			}
			$this->potionTypeRecipes[] = new PotionTypeRecipe($input, $inputMeta, $ingredient, $ingredientMeta, $output, $outputMeta);
		}
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			//TODO: we discard inbound ID here, not safe because netID on its own might map to internalID+internalMeta for us
			$inputIdNet = $this->getVarInt();
			$ingredientIdNet = $this->getVarInt();
			$outputIdNet = $this->getVarInt();

			if($this->protocol >= ProtocolInfo::PROTOCOL_419) {
				[$input, ] = ItemTranslator::getInstance()->fromNetworkId($inputIdNet, 0);
				[$ingredient, ] = ItemTranslator::getInstance()->fromNetworkId($ingredientIdNet, 0);
				[$output, ] = ItemTranslator::getInstance()->fromNetworkId($outputIdNet, 0);
			} else {
				$input = $inputIdNet;
				$ingredient = $ingredientIdNet;
				$output = $outputIdNet;
			}
			$this->potionContainerRecipes[] = new PotionContainerChangeRecipe($input, $ingredient, $output);
		}
		$this->cleanRecipes = (($this->get(1) !== "\x00"));
	}

	/**
	 * @param object              $entry
	 */
	private static function writeEntry($entry, NetworkBinaryStream $stream, int $pos) : int{
		if($entry instanceof ShapelessRecipe){
			return self::writeShapelessRecipe($entry, $stream, $pos);
		}elseif($entry instanceof ShapedRecipe){
			return self::writeShapedRecipe($entry, $stream, $pos);
		}elseif($entry instanceof FurnaceRecipe){
			return self::writeFurnaceRecipe($entry, $stream);
		}
		//TODO: add MultiRecipe

		return -1;
	}

	private static function writeShapelessRecipe(ShapelessRecipe $recipe, NetworkBinaryStream $stream, int $pos) : int{
		$stream->putString((\pack("N", $pos))); //some kind of recipe ID, doesn't matter what it is as long as it's unique
		$stream->putUnsignedVarInt($recipe->getIngredientCount());
		foreach($recipe->getIngredientList() as $item){
			$stream->putRecipeIngredient($item);
		}

		$results = $recipe->getResults();
		$stream->putUnsignedVarInt(count($results));
		foreach($results as $item){
			$stream->putItemStack($item);
		}

		$stream->put(str_repeat("\x00", 16)); //Null UUID
		$stream->putString("crafting_table"); //TODO: blocktype (no prefix) (this might require internal API breaks)
		$stream->putVarInt(50); //TODO: priority

		if($stream->getProtocol() >= ProtocolInfo::PROTOCOL_407) {
			$stream->writeGenericTypeNetworkId($pos); //TODO: ANOTHER recipe ID, only used on the network
		}
		return CraftingDataPacket::ENTRY_SHAPELESS;
	}

	private static function writeShapedRecipe(ShapedRecipe $recipe, NetworkBinaryStream $stream, int $pos) : int{
		$stream->putString((\pack("N", $pos))); //some kind of recipe ID, doesn't matter what it is as long as it's unique
		$stream->putVarInt($recipe->getWidth());
		$stream->putVarInt($recipe->getHeight());

		for($z = 0; $z < $recipe->getHeight(); ++$z){
			for($x = 0; $x < $recipe->getWidth(); ++$x){
				$stream->putRecipeIngredient($recipe->getIngredient($x, $z));
			}
		}

		$results = $recipe->getResults();
		$stream->putUnsignedVarInt(count($results));
		foreach($results as $item){
			$stream->putItemStack($item);
		}

		$stream->put(str_repeat("\x00", 16)); //Null UUID
		$stream->putString("crafting_table"); //TODO: blocktype (no prefix) (this might require internal API breaks)
		$stream->putVarInt(50); //TODO: priority

		if($stream->getProtocol() >= ProtocolInfo::PROTOCOL_407) {
			$stream->writeGenericTypeNetworkId($pos); //TODO: ANOTHER recipe ID, only used on the network
		}
		return CraftingDataPacket::ENTRY_SHAPED;
	}

	private static function writeFurnaceRecipe(FurnaceRecipe $recipe, NetworkBinaryStream $stream) : int{
		$input = $recipe->getInput();
		$result = CraftingDataPacket::ENTRY_FURNACE_DATA;

		if($stream->getProtocol() >= ProtocolInfo::PROTOCOL_419) {
			if($input->hasAnyDamageValue()){
				[$netId, ] = ItemTranslator::getInstance()->toNetworkId($input->getId(), 0);
				$netData = 0x7fff;
			}else{
				[$netId, $netData] = ItemTranslator::getInstance()->toNetworkId($input->getId(), $input->getDamage());
			}
			$stream->putVarInt($netId);
			$stream->putVarInt($netData);
		} else {
			$stream->putVarInt($input->getId());

			if(!$input->hasAnyDamageValue()){ //Data recipe
				$stream->putVarInt($input->getDamage());
			} else {
				$result = CraftingDataPacket::ENTRY_FURNACE;
			}
		}
		$stream->putItemStack($recipe->getResult());
		$stream->putString("furnace"); //TODO: blocktype (no prefix) (this might require internal API breaks)

		return $result;
	}

	/**
	 * @return void
	 */
	public function addShapelessRecipe(ShapelessRecipe $recipe){
		$this->entries[] = $recipe;
	}

	/**
	 * @return void
	 */
	public function addShapedRecipe(ShapedRecipe $recipe){
		$this->entries[] = $recipe;
	}

	/**
	 * @return void
	 */
	public function addFurnaceRecipe(FurnaceRecipe $recipe){
		$this->entries[] = $recipe;
	}

	protected function encodePayload(){
		$this->putUnsignedVarInt(count($this->entries));

		$writer = new NetworkBinaryStream();
		$writer->setProtocol($this->protocol);

		$counter = 0;
		foreach($this->entries as $d){
			$entryType = self::writeEntry($d, $writer, $counter++);
			if($entryType >= 0){
				$this->putVarInt($entryType);
				($this->buffer .= $writer->getBuffer());
			}else{
				$this->putVarInt(-1);
			}

			$writer->reset();
		}
		$this->putUnsignedVarInt(count($this->potionTypeRecipes));
		foreach($this->potionTypeRecipes as $recipe){
			$this->putVarInt($recipe->getInputItemId());
			$this->putVarInt($recipe->getInputItemMeta());
			$this->putVarInt($recipe->getIngredientItemId());
			$this->putVarInt($recipe->getIngredientItemMeta());
			$this->putVarInt($recipe->getOutputItemId());
			$this->putVarInt($recipe->getOutputItemMeta());
		}
		$this->putUnsignedVarInt(count($this->potionContainerRecipes));
		foreach($this->potionContainerRecipes as $recipe){
			$this->putVarInt($recipe->getInputItemId());
			$this->putVarInt($recipe->getIngredientItemId());
			$this->putVarInt($recipe->getOutputItemId());
		}

		($this->buffer .= ($this->cleanRecipes ? "\x01" : "\x00"));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCraftingData($this);
	}
}
