<?php
namespace RideTimeServer\API\Controllers;

use Slim\Http\Request;
use RideTimeServer\Exception\UserException;
use RideTimeServer\Entities\User;

trait ValidateUserTrait
{
    /**
     * Validate supplied ID matches ID of user
     * in Request 'currentUser' attribute
     *
     * @param Request $request
     * @param integer $id
     * @return User
     */
    protected function validateUser(Request $request, int $id): User
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('currentUser');

        if (!$currentUser) {
            throw new UserException('Current user not set', 403);
        }
        if ($currentUser->getId() !== $id) {
            $e = new UserException('Current user ID mismatch!', 403);
            $e->setData([
                'currentUser' => $currentUser->getId(),
                'user' => $id
            ]);
            throw $e;
        }

        return $currentUser;
    }
}
