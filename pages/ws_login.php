<?php
header('Content-Type: application/json');

if(!defined('INITIALIZED'))
    exit;
    
# error function
function sendError($msg){
	$ret = [];
	$ret["errorCode"] = 3;
	$ret["errorMessage"] = $msg;
	die(json_encode($ret));
}
$request = file_get_contents('php://input');
$result = json_decode($request);
$action = isset($result->type) ? $result->type : '';

switch ($action) {
	case 'cacheinfo':
		die(json_encode([
			'playersonline' => $status['players'],
			'twitchstreams' => 0,
			'twitchviewer' => 0,
			'gamingyoutubestreams' => 0,
			'gamingyoutubeviewer' => 0
		]));
	break;
	
	case 'eventschedule':
		die(json_encode([
			'eventlist' => []
		]));
	break;
	case 'boostedcreature':
		$boostDB = $SQL->query("select * from " . $SQL->tableName('boosted_creature'))->fetchAll();
		foreach ($boostDB as $Tableboost) {
		die(json_encode([
			'boostedcreature' => true,
			'raceid' => intval($Tableboost['raceid'])
		]));
		}
	break;
	case 'login':
	
		$port = Website::getServerConfig()->getValue('gameProtocolPort');
	
		// default world info
		$world = [
			'id' => 0,
			'name' => Website::getServerConfig()->getValue('serverName'),
			'externaladdress' => Website::getServerConfig()->getValue('ip'),
			'externalport' => $port,
			'externaladdressprotected' => Website::getServerConfig()->getValue('ip'),
			'externalportprotected' => $port,
			'externaladdressunprotected' => Website::getServerConfig()->getValue('ip'),
			'externalportunprotected' => $port,
			'previewstate' => 0,
			'location' => 'BRA', // BRA, EUR, USA
			'anticheatprotection' => false,
			'pvptype' => array_search(Website::getServerConfig()->getValue('worldType'), ['pvp', 'no-pvp', 'pvp-enforced']),
			'istournamentworld' => false,
			'restrictedstore' => false,
			'currenttournamentphase' => 2
		];
		$characters = [];
		$account = null;
		
		// common columns
		$columns = 'name, level, sex, vocation, looktype, lookhead, lookbody, looklegs, lookfeet, lookaddons, deleted, lastlogin';
		
		$account = new Account();
		$account->loadByEmail($result->email);
		$current_password = Website::encryptPassword($result->password);
		if (!$account->isLoaded() || !$account->isValidPassword($result->password)) {
			sendError('Email or password is not correct.');
		}
        	$players = $SQL->query("select {$columns} from players where account_id = " . $account->getId() . " order by name asc")->fetchAll();
		foreach ($players as $player) {
			$characters[] = create_char($player);
		}
		$worlds = [$world];
		$playdata = compact('worlds', 'characters');
		$session = [
			'sessionkey' => "$result->email\n$result->password",
			'lastlogintime' => (!$account) ? 0 : $account->getLastLogin(),
			'ispremium' => (!$account) ? true : $account->isPremium(),
			'premiumuntil' => (!$account) ? 0 : (time() + ($account->getPremDays() * 86400)),
			'status' => 'active', // active, frozen or suspended
			'returnernotification' => false,
			'showrewardnews' => true,
			'isreturner' => true,
			'fpstracking' => false,
			'optiontracking' => false,
			'tournamentticketpurchasestate' => 0,
			'emailcoderequest' => false
		];
		die(json_encode(compact('session', 'playdata')));
	break;
	
	default:
		sendError("Unrecognized event {$action}.");
	break;
}
function create_char($player) {
	return [
		'worldid' => 0,
		'name' => $player['name'],
		'ismale' => intval($player['sex']) === 1,
		'tutorial' => false, //intval($player['lastlogin']) === 0,
		'level' => intval($player['level']),
		'vocation' => Website::getVocationName($player['vocation']),
		'outfitid' => intval($player['looktype']),
		'headcolor' => intval($player['lookhead']),
		'torsocolor' => intval($player['lookbody']),
		'legscolor' => intval($player['looklegs']),
		'detailcolor' => intval($player['lookfeet']),
		'addonsflags' => intval($player['lookaddons']),
		'ishidden' => intval($player['deletion']) === 1,
		'istournamentparticipant' => false,
		'remainingdailytournamentplaytime' => 0
	];
}
