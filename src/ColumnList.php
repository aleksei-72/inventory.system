<?php


namespace App;


class ColumnList
{
    const itemSortingBy = ['id' => 'i.id',
        'title' => 'i.title',
        'comment' => 'i.comment',
        'count' => 'cast(substring(i.count, \'\d+\') AS Integer)',
        'created_at' => 'i.createdAt',
        'updated_at' => 'i.updatedAt',
        'profile' => 'i.profile',
        'number' => 'i.number',
        'price' => 'i.price',
        'category' => 'i.category'];
}