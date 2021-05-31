<?php


namespace App\Annotation;


use App\UserRoleList;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\MonologBundle\DependencyInjection\Compiler\LoggerChannelPass;

/**
 * Annotation class for @IsGrantedFor().
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class isGrantedFor {

    private $grantedRoles = array();

    public function __construct( $params) {

        if (!empty($params['roles'])) {
            $this->grantedRoles = $params['roles'];

            /*if (array_diff($this->grantedRoles, UserRoleList::RoleList)) {
                $logger = new Logger('main');
                $logger->error("undefined value of granted roles", $this->grantedRoles);
            }*/
        }
    }


    public function isAccessAllowed($role): bool {

        if (!in_array($role, $this->grantedRoles)) {
            return false;
        }

        return true;
    }
}