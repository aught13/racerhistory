<?php
require 'vendor/autoload.php';
require 'config/bootstrap.php';
use Cake\ORM\TableRegistry;

$table = TableRegistry::getTableLocator()->get('BlogTags');
$tags = $table->find()
    ->innerJoinWith('BlogPosts', function ($q) {
        return $q->where(['BlogPosts.is_published' => true]);
    })
    ->group(['BlogTags.id', 'BlogTags.name', 'BlogTags.slug'])
    ->select([
        'BlogTags.id',
        'BlogTags.name',
        'BlogTags.slug',
        'post_count' => $table->find()->func()->count('BlogPosts.id')
    ])
    ->orderByDesc('post_count')
    ->limit(10)
    ->all();

foreach ($tags as $t) {
    echo $t->name . ' (' . $t->post_count . ")\n";
}
