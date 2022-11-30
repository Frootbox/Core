<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com> 
 * @date 2019-02-27
 */

namespace Frootbox\Persistence;

class User extends \Frootbox\Persistence\AbstractRow
{
    use Traits\Uid;
    use Traits\Config;

    protected $table = 'users';
    protected $model = Repositories\Users::class;

    const ROLE_SUPERADMIN = 50;
    const ROLE_ADMIN = 40;
    const ROLE_EDITOR = 30;
    const ROLE_USER = 20;

    /**
     *
     */
    public function getGravatarUrl(): string
    {
        return 'https://www.gravatar.com/avatar/' . md5( strtolower( trim( $this->getEmail() ) ));
    }

    /**
     *
     */
    public function getName(): string
    {
        $name = trim($this->getFirstName() . ' ' . $this->getLastName());

        if (empty($name)) {
            $name = $this->getEmail();
        }

        return $name;
    }
    
    /**
     *
     */
    public function getSecureToken(): string
    {
        $date = new \DateTime('now');
        $date->modify('+1 day');

        $key = [
            $this->getId(),
            $date->format('Y-m-d H:i:s'),
            $this->getPassword()
        ];

        $key = $date->format('U') . '-' . md5(implode('##', $key));

        return $key;
    }


    /**
     *
     */
    public function setPassword(string $password): void
    {
        $this->data['password'] = password_hash($password, PASSWORD_DEFAULT);
        $this->changed['password'] = true;
    }

    /**
     *
     */
    public function validateSecureToken(string $token): void
    {
        // Extract validity
        [ $valid, $hash ] = explode('-', $token);

        // Check validity
        if ($valid - $_SERVER['REQUEST_TIME'] <= 0) {
            throw new \Frootbox\Exceptions\InputInvalid('TokenExpired');
        }

        $key = [
            $this->getId(),
            date('Y-m-d H:i:s', $valid),
            $this->getPassword()
        ];

        $key = $valid . '-' . md5(implode('##', $key));

        if ($key != $token) {
            throw new \Frootbox\Exceptions\InputInvalid('TokenInvalid');
        }
    }

    /**
     *
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->getPassword());
    }
}
