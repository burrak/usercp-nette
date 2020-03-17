<?php declare(strict_types = 1);

namespace App\Model;


use Nette;
use Nette\Security\AuthenticationException;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use PhpParser\Node\Stmt\Throw_;

class User implements Nette\Security\IAuthenticator
{
    /** @var \Nette\Database\Context */
    protected $db;
    /** @var \Nette\Security\Passwords */
    private $passwords;

    public function __construct(
        Nette\Database\Context $db
    )
    {
        $this->db = $db;
    }


    /**
     * @param string[] $credentials
     * @return \Nette\Security\Identity
     * @throws \Nette\Security\AuthenticationException
     */
    public function authenticate(array $credentials): Nette\Security\Identity
    {
        [$username, $password] = $credentials;

        $row = $this->db->query("SELECT acc.id, acc.username, acc.sha_pass_hash, acc.reg_mail, aca.gmlevel FROM account acc LEFT JOIN account_access aca ON acc.id = aca.id WHERE acc.username = ?", $username)->fetch();

        if (!$row) {
            throw new \Nette\Security\AuthenticationException('Tento účet není zaregistrovaný');
        } elseif ($this->hashPassword($username, $password) !== $row["sha_pass_hash"]) {
            throw new \Nette\Security\AuthenticationException('Špatné heslo');
        }


        return new Nette\Security\Identity($row['id'], $row['gmlevel'], ['username' => $row['username']]);

    }

    /**
     * @param string $login
     * @return mixed[]
     */
    private function getDataLogin(string $login): array
    {
        return $this->db->query('SELECT * FROM auth.account WHERE username=?', $login)->fetch();
    }

    /**
     * @param string $email
     * @return mixed[]|null
     */
    private function checkEmail(string $email): ?array
    {
        return $this->db->query('SELECT id FROM auth.account WHERE reg_mail=?', $email)->fetch();
    }

    /**
     * @param int $accId
     * @return mixed[]
     */
    private function getPassword(int $accId): array
    {
        return $this->db->query('SELECT acc.sha_pass_hash FROM account acc WHERE acc.id = ?', $accId)->fetch();
    }

    protected function hashPassword(string $login, string $password): string
    {
        $login = \strtoupper($login);
        $password = \strtoupper($password);
        $hash = \sha1($login . ':' . $password);
        $hash = \strtoupper($hash);
        return $hash;
    }

    public function checkPassword(int $acc_id, string $password): bool
    {
        $creds = $this->getDataId($acc_id);
        $password_hash = $creds['sha_pass_hash'];
        $login = $creds['username'];
        $sha_pass = $this->hashPassword($login, $password);
        if ($sha_pass === $password_hash) {
            return true;
        }
            return false;
    }

    public function registrace(string $login, string $password, string $email): void
    {
        $accountCheck = $this->getDataLogin($login);
        $emailCheck = $this->checkEmail($email);

        if (isset($accountCheck['id'])) {
            throw new \Nette\Security\AuthenticationException('Uživatel s daným loginem již existuje');
        }
        if (isset($emailCheck['id'])) {
            throw new \Nette\Security\AuthenticationException('Tento e-mail je již zaregistrovaný');
        }
        $hash = $this->hashPassword($login, $password);
        try {
            $this->db->query('INSERT INTO account (username, sha_pass_hash, reg_mail) VALUES (?, ?, ?)', $login, $hash, $email);
        } catch (\Throwable $t) {
            throw new \Nette\Security\AuthenticationException('Chyba při registraci, zkuste to znovu');
        }

    }

    /**
     * @param int $account_id
     * @return mixed[]
     */
    public function getDataId(int $account_id): array
    {
        $result = $this->db->query('SELECT * FROM account WHERE id=?', $account_id)->fetch();
        return $result;
    }

    public function returnLogin(int $account_id): string
    {
        $data = $this->db->query('SELECT username FROM account WHERE id=?', $account_id)->fetch();
        return $data['username'];
    }

    public function returnCredits(int $account_id): int
    {
        $data = $this->db->query('SELECT credits FROM account WHERE id=?', $account_id)->fetch();
        return $data['credits'];
    }

    public function returnLastIp(int $account_id): string
    {
        $data = $this->db->query('SELECT last_ip FROM account WHERE id=?', $account_id)->fetch();
        return $data['last_ip'];
    }

    public function changePassword(int $account_id, string $pass_new): void
    {
        $login_str = $this->getDataId($account_id);
        $password_hash = $this->hashPassword($login_str['username'], $pass_new);
        $this->db->query('UPDATE auth.account SET sha_pass_hash=? WHERE id=?', $password_hash, $login_str['id']);
    }

}