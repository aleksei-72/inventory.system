<?php


namespace App;


class UserRoleList
{

    const U_ADMIN = 'admin';
    const U_USER = 'user';
    const U_READONLY = 'reader';

    const RoleList = [
        //Только для @IsGrantedFor
        'guest',
        self::U_READONLY,
        self::U_USER,
        self::U_ADMIN
    ];

}