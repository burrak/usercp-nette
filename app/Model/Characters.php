<?php declare(strict_types = 1);

namespace App\Model;

use Nette;

class Characters
{

    private const AT_LOGIN_RENAME = 1;
    private const AT_LOGIN_RESET_SPELLS = 2;
    private const AT_LOGIN_RESET_TALENTS = 4;
    private const AT_LOGIN_CUSTOMIZE = 8;
    private const AT_LOGIN_RESET_PET_TALENTS = 16;
    private const AT_LOGIN_FIRST = 32;
    private const AT_LOGIN_CHANGE_FACTION = 64;
    private const AT_LOGIN_CHANGE_RACE = 128;

    /** @var \Nette\Database\Context */
    protected $db;
    /** @var \App\Model\User */
    protected $user;

    public function __construct(
        Nette\Database\Context $db,
        \App\Model\User $user
    )
    {
        $this->db = $db;
        $this->user = $user;
    }

    /**
     * @param int $account_id
     * @param bool $race
     * @param bool $class
     * @return mixed[]
     */
    public function getPostavy(int $account_id, bool $race, bool $class): array
    {
        $postavy = $this->db->query('SELECT * FROM characters.characters WHERE account=?', $account_id)->fetchAll();
        foreach ($postavy as $key => &$char) {
            if ($race === true) {
                $char['race'] = $this->raceHandler($char['race']);
            }
            if ($class === false) {
                continue;
            }
            $char['class'] = $this->classHandler($char['class']);
        }
        return $postavy;
    }

    private function raceHandler(int $rasa): string
    {
        switch ($rasa) {
            case 1:
                return 'Human';
                break;

            case 2:
                return 'Orc';
                break;

            case 3:
                return 'Dwarf';
                break;

            case 4:
                return 'Night Elf';
                break;

            case 5:
                return 'Undead';
                break;

            case 6:
                return 'Tauren';
                break;

            case 7:
                return 'Gnome';
                break;

            case 8:
                return 'Troll';
                break;

            case 10:
                return 'Blood Elf';
                break;

            case 11:
                return 'Draenei';
                break;
        }
    }

    private function classHandler(int $class): string
    {
        switch ($class) {
            case 1:
                return 'Warrior';
                break;

            case 2:
                return 'Paladin';
                break;

            case 3:
                return 'Hunter';
                break;

            case 4:
                return 'Rogue';
                break;

            case 5:
                return 'Priest';
                break;

            case 6:
                return 'Death Knight';
                break;

            case 7:
                return 'Shaman';
                break;

            case 8:
                return 'Mage';
                break;

            case 9:
                return 'Warlock';
                break;

            case 11:
                return 'Druid';
                break;
        }
    }

    /**
     * @return mixed[]
     */
    public function returnSluzby(): array
    {
        return $this->db->query('SELECT * FROM ucp_prices WHERE list=1')->fetchAll();
    }

    public function unstuck(int $account_id, int $postava_id, int $credits): void
    {
        $homebind = $this->db->query('SELECT * FROM characters.character_homebind WHERE guid=?', $postava_id)->fetch();
        $this->db->query('DELETE FROM characters.character_aura WHERE guid=?', $postava_id);
        $this->db->query('UPDATE characters.characters SET playerFlags=0, position_x=?, position_y=?, position_z=?, map=?, unstuckCooldown=DATE_ADD(NOW(), INTERVAL 5 HOUR) WHERE guid=?', $homebind[0]['posX'], $homebind[0]['posY'], $homebind[0]['posZ'], $homebind[0]['mapId'], $postava_id);
        $this->db->query('UPDATE auth.account SET credits = ? WHERE id=?', $credits, $account_id);
    }

    public function rename(int $account_id, int $postava_id, int $credits): ?bool
    {
        $char = $this->getJednaPostava($postava_id);
        if ($char['at_login'] & self::AT_LOGIN_RENAME) {
            return false;
        }
        $set_at_login = $char['at_login'] | self::AT_LOGIN_RENAME;
        $this->db->query('UPDATE characters.characters SET at_login=? WHERE guid=?', $set_at_login, $postava_id);
        $this->db->query('UPDATE account SET credits = ? WHERE id=?', $credits, $account_id);

    }

    public function postavaCheck(int $account_id, int $postava_id, int $je_smazana=0): ?string
    {
        $postava_check = $this->getJednaPostava($postava_id);

        if (\count($postava_check) === 0) {
            return 'char_not_exist';
        }
        if ((($postava_check['account'] !== $account_id) && ($je_smazana === 0)) || (($postava_check['deleteInfos_Account'] !== $account_id) && ($je_smazana === 1))) {
            return 'char_not_owned';
        }
    }

    /**
     * @param int $postava_id
     * @return mixed[]
     */
    public function getJednaPostava(int $postava_id): array
    {
        return $this->db->query('SELECT * FROM characters.characters WHERE guid=? LIMIT 1', $postava_id)->fetch();
    }

    public function postavaCheckOnline(int $postava_id, int $postava_online): bool
    {
        if ($postava_online === 1) {
            return true;
        }
            return false;
    }

    public function priceCheck(int $account_id, int $service): ?int
    {
        $credits = $this->user->returnCredits($account_id);
        $price = $this->db->query('SELECT price FROM ucp_prices WHERE id=?', $service)->fetch();

        if ($credits >= $price['price']) {
            return $credits - $price['price'];
        }
    }

    /**
     * @param int $postava_id
     * @param bool $race
     * @param bool $class
     * @return mixed[]
     */
    public function returnPostava(int $postava_id, bool $race, bool $class): array
    {
        $postava = $this->getJednaPostava($postava_id);

        if ($race === true) {
            $postava['race'] = $this->raceHandler($postava['race']);
        }
        if ($class === true) {
            $postava['class'] = $this->classHandler($postava['class']);
        }

        return $postava;
    }

    public function raceChange(int $account_id, int $postava_id, int $credits): ?bool
    {
        $postava = $this->getJednaPostava($postava_id);
        if ($postava['at_login'] & self::AT_LOGIN_CHANGE_RACE) {
            return false;
        }
        $set_at_login = $postava['at_login'] | self::AT_LOGIN_CHANGE_RACE;
        $this->db->query('UPDATE characters.characters SET at_login=? WHERE guid=?', $set_at_login, $postava_id);
        $this->db->query('UPDATE auth.account SET credits=? WHERE id=?', $credits, $account_id);

    }

    public function factionchange(int $account_id, int $postava_id, int $credits): ?bool
    {
        $postava = $this->getJednaPostava($postava_id);
        if ($postava['at_login'] & self::AT_LOGIN_CHANGE_FACTION) {
            return false;
        } else {
            $set_at_login = $postava['at_login'] | self::AT_LOGIN_CHANGE_FACTION;
            $this->db->query('UPDATE characters.characters SET at_login=? WHERE guid=?', $set_at_login, $postava_id);
            $this->db->query('UPDATE auth.account SET credits=? WHERE id=?', $credits, $account_id);
        }
    }

    public function apperancechange(int $account_id, int $postava_id, int $credits): ?bool
    {
        $postava = $this->getJednaPostava($postava_id);
        if ($postava['at_login'] & self::AT_LOGIN_CUSTOMIZE) {
            return false;
        } else {
            $set_at_login = $postava['at_login'] | self::AT_LOGIN_CUSTOMIZE;
            $this->db->query('UPDATE characters.characters SET at_login=? WHERE guid=?', $set_at_login, $postava_id);
            $this->db->query('UPDATE auth.account SET credits=? WHERE id=?', $credits, $account_id);
        }
    }

    public function obnovPostavu(int $postava_id): void
    {
            $this->db->query('UPDATE characters.characters SET account=deleteInfos_Account, name=guid, deleteInfos_Account= NULL, deleteDate= NULL, deleteInfos_Name= NULL, at_login=1 WHERE guid=?', $postava_id);
    }

    /**
     * @param int $account_id
     * @param bool $race
     * @param bool $class
     * @return mixed[]|null
     */
    public function returnSmazanePostavy(int $account_id, bool $race, bool $class): ?array
    {
        return $this->getSmazanePostavy($account_id, $race, $class);
    }

    /**
     * @param int $account_id
     * @param bool $race
     * @param bool $class
     * @return mixed[]|null
     */
    private function getSmazanePostavy(int $account_id, bool $race, bool $class): ?array
    {
        $smazane = $this->db->query('SELECT * FROM characters.characters WHERE deleteInfos_Account=?', $account_id)->fetchAll();
        foreach ($smazane as $key => &$char) {
            if ($race === true) {
                $char['race'] = $this->raceHandler($char['race']);
            }
            if ($class === false) {
                continue;
            }
            $char['class'] = $this->classHandler($char['class']);
        }
        return $smazane;
    }

    public function postavaTrade(int $account_id, string $postava_required, int $postava_owned): bool
    {
        $postava_required_db = $this->getJednaPostavaByName($postava_required);
        $postava_owned_db = $this->getJednaPostava($postava_owned);
        $this->db->query('INSERT INTO characters.trade (char1, char2, acc1, acc2, status) VALUES (?, ?, ?, ?, 0)', $postava_owned_db['guid'], $postava_required_db['guid'], $account_id, $postava_required_db['account']);
    }

    /**
     * @param string $postava_name
     * @return mixed[]|null
     */
    private function getJednaPostavaByName(string $postava_name): ?array
    {
        return $this->db->query('SELECT * FROM characters.characters WHERE name=? LIMIT 1', $postava_name)->fetchAll();
    }

    /**
     * @param int $account_id
     * @return mixed[]|null
     */
    public function tradeByMe(int $account_id): ?array
    {
        $trade = $this->db->query('SELECT characters.trade.*, characters.char1.name AS "char1_name", characters.char1.race AS "char1_race", characters.char1.class AS "char1_class", characters.char1.level AS "char1_level", characters.char2.name AS "char2_name", characters.char2.race AS "char2_race", characters.char2.class AS "char2_class", characters.char2.level AS "char2_level" FROM characters.trade LEFT JOIN characters.characters char1 ON characters.trade.char1 = characters.char1.guid LEFT JOIN characters.characters char2 ON characters.trade.char2 = characters.char2.guid WHERE characters.trade.acc1=? AND characters.trade.status=0', $account_id)->fetchAll();
        foreach ($trade as $key => &$char) {
            $char['char1_race'] = $this->raceHandler($char['char1_race']);
            $char['char1_class'] = $this->classHandler($char['char1_class']);
            $char['char2_race'] = $this->raceHandler($char['char2_race']);
            $char['char2_class'] = $this->classHandler($char['char2_class']);

        }
        return $trade;
    }

    /**
     * @param int $account_id
     * @return mixed[]|null
     */
    public function tradeToMe(int $account_id): ?array
    {
        $trade = $this->db->query('SELECT characters.trade.*, characters.char1.name AS "char1_name", characters.char1.race AS "char1_race", characters.char1.class AS "char1_class", characters.char1.level AS "char1_level", characters.char2.name AS "char2_name", characters.char2.race AS "char2_race", characters.char2.class AS "char2_class", characters.char2.level AS "char2_level" FROM characters.trade LEFT JOIN characters.characters char1 ON characters.trade.char1 = characters.char1.guid LEFT JOIN characters.characters char2 ON characters.trade.char2 = characters.char2.guid WHERE characters.trade.acc2=? AND characters.trade.status=0', $account_id)->fetchAll();
        foreach ($trade as $key => &$char) {
            $char['char1_race'] = $this->raceHandler($char['char1_race']);
            $char['char1_class'] = $this->classHandler($char['char1_class']);
            $char['char2_race'] = $this->raceHandler($char['char2_race']);
            $char['char2_class'] = $this->classHandler($char['char2_class']);

        }
        return $trade;
    }

    public function confirmTrade(int $trade_id): void
    {
        $result = $this->db->query('SELECT * FROM characters.trade WHERE id=?', $trade_id)->fetch();
        $this->db->query('UPDATE characters.characters SET account=? WHERE guid=?', $result['acc1'], $result['char2']);
        $this->db->query('UPDATE characters.characters SET account=? WHERE guid=?', $result['char1'], $result['acc2']);
        $this->db->query('UPDATE characters.trade SET status=1 WHERE id=?', $trade_id);
    }

    public function cancelTrade(int $trade_id): void
    {
        $this->db->query("DELETE FROM characters.trade WHERE id = ?", $trade_id);
    }

}
