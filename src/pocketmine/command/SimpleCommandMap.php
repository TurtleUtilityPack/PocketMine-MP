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

namespace pocketmine\command;

use pocketmine\command\defaults\BanCommand;
use pocketmine\command\defaults\BanIpCommand;
use pocketmine\command\defaults\BanListCommand;
use pocketmine\command\defaults\DefaultGamemodeCommand;
use pocketmine\command\defaults\DeopCommand;
use pocketmine\command\defaults\DifficultyCommand;
use pocketmine\command\defaults\DumpMemoryCommand;
use pocketmine\command\defaults\EffectCommand;
use pocketmine\command\defaults\EnchantCommand;
use pocketmine\command\defaults\GamemodeCommand;
use pocketmine\command\defaults\GarbageCollectorCommand;
use pocketmine\command\defaults\GiveCommand;
use pocketmine\command\defaults\HelpCommand;
use pocketmine\command\defaults\KickCommand;
use pocketmine\command\defaults\KillCommand;
use pocketmine\command\defaults\ListCommand;
use pocketmine\command\defaults\OpCommand;
use pocketmine\command\defaults\PardonCommand;
use pocketmine\command\defaults\PardonIpCommand;
use pocketmine\command\defaults\ParticleCommand;
use pocketmine\command\defaults\PluginsCommand;
use pocketmine\command\defaults\ReloadCommand;
use pocketmine\command\defaults\SaveCommand;
use pocketmine\command\defaults\SaveOffCommand;
use pocketmine\command\defaults\SaveOnCommand;
use pocketmine\command\defaults\SayCommand;
use pocketmine\command\defaults\SeedCommand;
use pocketmine\command\defaults\SetWorldSpawnCommand;
use pocketmine\command\defaults\SpawnpointCommand;
use pocketmine\command\defaults\StatusCommand;
use pocketmine\command\defaults\StopCommand;
use pocketmine\command\defaults\TeleportCommand;
use pocketmine\command\defaults\TellCommand;
use pocketmine\command\defaults\TimeCommand;
use pocketmine\command\defaults\TimingsCommand;
use pocketmine\command\defaults\TitleCommand;
use pocketmine\command\defaults\TransferServerCommand;
use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\defaults\WhitelistCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\Server;
use function array_shift;
use function count;
use function explode;
use function implode;
use function min;
use function preg_match_all;
use function strcasecmp;
use function stripslashes;
use function strpos;
use function strtolower;
use function trim;

class SimpleCommandMap implements CommandMap{

	/** @var Command[] */
	protected $knownCommands = [];

	/** @var Server */
	private $server;

	public function __construct(Server $server){
		$this->server = $server;
		$this->setDefaultCommands();
	}

	private function setDefaultCommands() : void{
		$server = Server::getInstance();
		// default commands
		$commands = [
			new DefaultGamemodeCommand("defaultgamemode"),
			new DumpMemoryCommand("dumpmemory"),
			new WhitelistCommand("whitelist"),
			new GarbageCollectorCommand("gc"),
			new PardonIpCommand("pardon-ip"),
			new SaveOffCommand("save-off"),
			new TimingsCommand("timings"),
			new BanListCommand("banlist"),
			new SaveOnCommand("save-on"),
			new PardonCommand("pardon"),
			new SaveCommand("save-all"),
			new StatusCommand("status"),
			new BanIpCommand("ban-ip"),
			new BanCommand("ban")
		];
		if($server->getProperty('definitions.commands.enabled', true)) {
			if($server->getProperty('definitions.command.defaults.deop', true))
				$commands[] = new DeopCommand('deop');
			if($server->getProperty('definitions.command.defaults.op', true))
				$commands[] = new OpCommand('op');

			if($server->getProperty('definitions.command.defaults.stop', true))
				$commands[] = new StopCommand('stop');
			if($server->getProperty('definitions.command.defaults.reload', true))
				$commands[] = new ReloadCommand('reload');

			if($server->getProperty('definitions.command.defaults.transferserver', true))
				$commands[] = new TransferServerCommand('transferserver');
			if($server->getProperty('definitions.command.defaults.setworldspawn', true))
				$commands[] = new SetWorldSpawnCommand('setworldspawn');
			
			if($server->getProperty('definitions.command.defaults.spawnpoint', true))
				$commands[] = new SpawnpointCommand('spawnpoint');
			if($server->getProperty('definitions.command.defaults.difficulty', true))
				$commands[] = new DifficultyCommand('difficulty');
			
			if($server->getProperty('definitions.command.defaults.teleport', true))
				$commands[] = new TeleportCommand('tp');
			if($server->getProperty('definitions.command.defaults.gamemode', true))
				$commands[] = new GamemodeCommand('gamemode');
			
			if($server->getProperty('definitions.command.defaults.plugins', true))
				$commands[] = new PluginsCommand('plugins');
			if($server->getProperty('definitions.command.defaults.enchant', true))
				$commands[] = new EnchantCommand('enchant');

			if($server->getProperty('definitions.command.defaults.effect', true))
				$commands[] = new EffectCommand('effect');
			if($server->getProperty('definitions.command.defaults.title', true))
				$commands[] = new TitleCommand('title');

			if($server->getProperty('definitions.command.defaults.kill', true))
				$commands[] = new KillCommand('kill');
			if($server->getProperty('definitions.command.defaults.help', true))
				$commands[] = new HelpCommand('help');

			if($server->getProperty('definitions.command.defaults.give', true))
				$commands[] = new GiveCommand('give');
			if($server->getProperty('definitions.command.defaults.list', true))
				$commands[] = new ListCommand('list');

			if($server->getProperty('definitions.command.defaults.seed', true))
				$commands[] = new SeedCommand('seed');
			if($server->getProperty('definitions.command.defaults.time', true))
				$commands[] = new TimeCommand('time');

			if($server->getProperty('definitions.command.defaults.say', true))
				$commands[] = new SayCommand('say');
			if($server->getProperty('definitions.command.defaults.tell', true))
				$commands[] = new TellCommand('tell');
		}
		$this->registerAll("pocketmine", $commands);
	}

	public function registerAll(string $fallbackPrefix, array $commands){
		foreach($commands as $command){
			$this->register($fallbackPrefix, $command);
		}
	}

	public function register(string $fallbackPrefix, Command $command, string $label = null) : bool{
		if($label === null){
			$label = $command->getName();
		}
		$label = trim($label);
		$fallbackPrefix = strtolower(trim($fallbackPrefix));

		$registered = $this->registerAlias($command, false, $fallbackPrefix, $label);

		$aliases = $command->getAliases();
		foreach($aliases as $index => $alias){
			if(!$this->registerAlias($command, true, $fallbackPrefix, $alias)){
				unset($aliases[$index]);
			}
		}
		$command->setAliases($aliases);

		if(!$registered){
			$command->setLabel($fallbackPrefix . ":" . $label);
		}
		$command->register($this);

		return $registered;
	}

	public function unregister(Command $command) : bool{
		foreach($this->knownCommands as $lbl => $cmd){
			if($cmd === $command){
				unset($this->knownCommands[$lbl]);
			}
		}

		$command->unregister($this);

		return true;
	}

	private function registerAlias(Command $command, bool $isAlias, string $fallbackPrefix, string $label) : bool{
		// $this->knownCommands[$fallbackPrefix . ":" . $label] = $command;
		if(($command instanceof VanillaCommand or $isAlias) and isset($this->knownCommands[$label])){
			return false;
		}

		if(isset($this->knownCommands[$label]) and $this->knownCommands[$label]->getLabel() === $label){
			return false;
		}

		if(!$isAlias){
			$command->setLabel($label);
		}

		$this->knownCommands[$label] = $command;

		return true;
	}

	/**
	 * Returns a command to match the specified command line, or null if no matching command was found.
	 * This method is intended to provide capability for handling commands with spaces in their name.
	 * The referenced parameters will be modified accordingly depending on the resulting matched command.
	 *
	 * @param string   $commandName reference parameter
	 * @param string[] $args reference parameter
	 *
	 * @return Command|null
	 */
	public function matchCommand(string &$commandName, array &$args){
		$count = min(count($args), 255);

		for($i = 0; $i < $count; ++$i){
			$commandName .= array_shift($args);
			if(($command = $this->getCommand($commandName)) instanceof Command){
				return $command;
			}

			$commandName .= " ";
		}

		return null;
	}

	public function dispatch(CommandSender $sender, string $commandLine) : bool{
		$args = [];
		preg_match_all('/"((?:\\\\.|[^\\\\"])*)"|(\S+)/u', $commandLine, $matches);
		foreach($matches[0] as $k => $_){
			for($i = 1; $i <= 2; ++$i){
				if($matches[$i][$k] !== ""){
					$args[$k] = stripslashes($matches[$i][$k]);
					break;
				}
			}
		}
		$sentCommandLabel = "";
		$target = $this->matchCommand($sentCommandLabel, $args);

		if($target === null){
			return false;
		}

		$target->timings->startTiming();

		try{
			$target->execute($sender, $sentCommandLabel, $args);
		}catch(InvalidCommandSyntaxException $e){
			$sender->sendMessage($this->server->getLanguage()->translateString("commands.generic.usage", [$target->getUsage()]));
		}finally{
			$target->timings->stopTiming();
		}

		return true;
	}

	public function clearCommands(){
		foreach($this->knownCommands as $command){
			$command->unregister($this);
		}
		$this->knownCommands = [];
		$this->setDefaultCommands();
	}

	public function getCommand(string $name){
		return $this->knownCommands[$name] ?? null;
	}

	/**
	 * @return Command[]
	 */
	public function getCommands() : array{
		return $this->knownCommands;
	}

	/**
	 * @return void
	 */
	public function registerServerAliases(){
		$values = $this->server->getCommandAliases();

		foreach($values as $alias => $commandStrings){
			if(strpos($alias, ":") !== false){
				$this->server->getLogger()->warning($this->server->getLanguage()->translateString("pocketmine.command.alias.illegal", [$alias]));
				continue;
			}

			$targets = [];
			$bad = [];
			$recursive = [];

			foreach($commandStrings as $commandString){
				$args = explode(" ", $commandString);
				$commandName = "";
				$command = $this->matchCommand($commandName, $args);

				if($command === null){
					$bad[] = $commandString;
				}elseif(strcasecmp($commandName, $alias) === 0){
					$recursive[] = $commandString;
				}else{
					$targets[] = $commandString;
				}
			}

			if(count($recursive) > 0){
				$this->server->getLogger()->warning($this->server->getLanguage()->translateString("pocketmine.command.alias.recursive", [$alias, implode(", ", $recursive)]));
				continue;
			}

			if(count($bad) > 0){
				$this->server->getLogger()->warning($this->server->getLanguage()->translateString("pocketmine.command.alias.notFound", [$alias, implode(", ", $bad)]));
				continue;
			}

			//These registered commands have absolute priority
			if(count($targets) > 0){
				$this->knownCommands[strtolower($alias)] = new FormattedCommandAlias(strtolower($alias), $targets);
			}else{
				unset($this->knownCommands[strtolower($alias)]);
			}

		}
	}
}
