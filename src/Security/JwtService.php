<?php

namespace Varhall\Restino\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Nette\Http\Request;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;
use Nette\Security\UserStorage;
use Nette\Utils\DateTime;
use Varhall\Utilino\Utils\Guid;


class JwtService implements UserStorage
{
    protected Request $request;

    protected string $algorithm = 'HS256';
    protected string $key;
    protected int $expiration   = 1440;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }


    /// Getters & Setters

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function setAlgorithm(string $algorithm): void
    {
        $this->algorithm = $algorithm;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getTokenExpiration(): int
    {
        return $this->expiration;
    }

    public function setTokenExpiration(int $expiration): void
    {
        $this->expiration = $expiration;
    }



    /// Application methods

    public function createToken(IIdentity $identity): string
    {
        $now = new DateTime();

        $data = [
            'iat'   => $now->getTimestamp(),                                                        // Issued at: time when the token was generated
            'jti'   => Guid::generate(),                                                            // Json Token Id: an unique identifier for the token
            'iss'   => $_SERVER['SERVER_NAME'],                                                     // Issuer
            'nbf'   => $now->getTimestamp(),                                                        // Not before
            'exp'   => $now->modifyClone("+{$this->expiration} minutes")->getTimestamp(),   // Expire
            'sub'   => $identity->getId(),
            'roles' => $identity->getRoles(),
            'data'  => $identity->getData()
        ];

        return JWT::encode($data, $this->key, $this->algorithm);
    }


    /// UserStorage

    public function saveAuthentication(IIdentity $identity): void
    {
    }

    public function clearAuthentication(bool $clearIdentity): void
    {
    }

    public function getState(): array
    {
        $token = $this->request->getHeader('Authorization');

        if (empty($token) || !preg_match('/^Bearer (.+)$/i', $token, $matches)) {
            return [ false, null, null ];
        }

        try {
            $data = JWT::decode($matches[1], new Key($this->key, $this->algorithm));
            $identity = new SimpleIdentity($data->sub, $data->roles, (array)($data->data ?? []));

            return [true, $identity, null];

        } catch (\Exception $ex) {
            return [ false, null, null ];
        }
    }

    public function setExpiration(?string $expire, bool $clearIdentity): void
    {
    }
}
