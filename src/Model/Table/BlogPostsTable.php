<?php
namespace CakephpSpongeBlog\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use CakephpSpongeBlog\Model\Entity\BlogPost;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Cache\Cache;

/**
 * BlogPosts Model
 *
 */
class BlogPostsTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        $settings = Configure::read('settings');
        $this->setTable('blog_posts');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->addBehavior('Tools.Slugged', ['label' => 'title', 'unique' => true, 'case' => 'low']);
        $this->addBehavior('Proffer.Proffer', [
            'photo' => [
                'root' => $settings['blog']['image-directory'],
                'dir' => 'photo_dir',
                'thumbnailSizes' => [
                    'index' => ['w' => $settings['blog']['image-dimension-on-post-index'], 'h' => $settings['blog']['image-dimension-on-post-index'], 'fit' => true],  // used in index action and edit action
                    'view' => [ // used in view action
                        'jpeg_quality' => 75,
                        'custom' => 'resize',
                        'params' => [
                            $settings['blog']['max-image-width'], // 2x max width of containing element: #blogposts article
                            null,
                            function ($constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            }
                        ]
                    ],
                ],
            ]
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->add('id', 'valid', ['rule' => 'numeric'])
            ->allowEmptyString('id', null, 'create');
        $validator
            ->requirePresence('title', 'create')
            ->allowEmptyString('title');

        $validator
            ->allowEmptyString('summary');

        $validator
            ->allowEmptyString('body');

        $validator
            ->add('photo', 'file', [
            'rule' => ['uploadedFile', [
                'optional' => true,
                'maxSize' => '1000000',
                'types' => ['image/jpeg', 'image/png']
            ]],
            'message' => 'Photo must be a jpeg or png that is no larger than 1MB'
        ])
            ->allowEmptyFile('photo');

        $validator
            ->add('published', 'valid', ['rule' => 'boolean'])
            ->requirePresence('published', 'create')
            ->notEmptyString('published');

        $validator
            ->add('sticky', 'valid', ['rule' => 'boolean'])
            ->requirePresence('sticky', 'create')
            ->notEmptyString('sticky');

        $validator
            ->add('in_rss', 'valid', ['rule' => 'boolean'])
            ->requirePresence('in_rss', 'create')
            ->notEmptyString('in_rss');

        $validator
            ->allowEmptyString('meta_title');

        $validator
            ->allowEmptyString('meta_description');

        return $validator;
    }

    public function findSlugged(Query $query, array $options)
    {
        $query->where([
            'BlogPosts.slug' => $options['slug'],
            'BlogPosts.published' => true
        ]);
        $query->cache(function ($q) use ($options) {
            return 'blogpost-' . $options['slug'];
        });
        return $query;
    }

    public function findLatest(Query $query, array $options)
    {
        $query->where([
            'BlogPosts.published' => true
        ])
        ->limit(2)
        ->order(['created' => 'desc']);
        return $query;
    }

    public function findArchive(Query $query, array $options)
    {
        $query->where([
            'BlogPosts.published' => true
        ]);
        $fdate = $query->func()->date_format([
            'created' => 'identifier',
            "'%Y %m'" => 'literal'
        ]);
        $year = $query->func()->date_format([
            'created' => 'identifier',
            "'%Y'" => 'literal'
        ]);
        $fullmonth = $query->func()->date_format([
            'created' => 'identifier',
            "'%M'" => 'literal'
        ]);
        $month = $query->func()->date_format([
            'created' => 'identifier',
            "'%m'" => 'literal'
        ]);
        $query->select([
            'fdate' => $fdate,
            'year' => $year,
            'fullmonth' => $fullmonth,
            'month' => $month,
            'total_posts' => $query->func()->count('BlogPosts.id')
        ])
        ->order(['fdate' => 'desc'])
        ->group(['fdate', 'year', 'fullmonth', 'month']);
        return $query;
    }

    public function afterDelete(Event $event, $entity, $options)
    {
        Cache::delete('blogpost-' .  $entity->slug);
        Cache::delete('cell_cakephp_sponge_blog_view_cell_news_cell_display_default');
        Cache::delete('cell_cakephp_sponge_blog_view_cell_archive_cell_display_default');
    }

    public function afterSave(Event $event, $entity, $options)
    {
        Cache::delete('blogpost-' .  $entity->slug);
        Cache::delete('cell_cakephp_sponge_blog_view_cell_news_cell_display_default');
        Cache::delete('cell_cakephp_sponge_blog_view_cell_archive_cell_display_default');
    }
}
