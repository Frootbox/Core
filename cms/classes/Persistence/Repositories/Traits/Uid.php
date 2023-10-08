<?php
/**
 *
 */

namespace Frootbox\Persistence\Repositories\Traits;

trait Uid
{
    /**
     *
     */
    public function __fetchByUid($uid, array $options = null)
    {
        // Fetch text
        $text = $this->fetchOne([
            'where' => [
                'uid' => $uid,
              //  'language' => $_SESSION['frontend']['language'],
            ],
        ]);

        if ($text === null) {

            if (empty($options['createOnMiss'])) {
                return null;
            }

            $className = $this->class;

            $text = $this->insert(new $className([
                'uid' => $uid,
                'userId' => '{userId}',
                'language' => $_SESSION['frontend']['language'],
            ]));
        }

        return $text;
    }

    /**
     *
     */
    public function fetchByUid($uid, array $options = null)
    {
        $where = [
            'uid' => $uid,
        ];

        if (MULTI_LANGUAGE and empty($options['ignoreLanguage']) and !empty($_SESSION['frontend']['language'])) {
            $where['language'] = $_SESSION['frontend']['language'];
        }

        // Fetch row
        $query = [
            'where' => $where,
        ];

        if (!empty($options['order'])) {
            $query['order'] = [ $options['order'] ];
        }

        $text = $this->fetchOne($query);


        if ($text === null) {

            if (!empty($options['createOnMiss'])) {

                // Re-check for language
                if (DEFAULT_LANGUAGE != $_SESSION['frontend']['language']) {

                    $text = $this->fetchOne([
                        'where' => [
                            'uid' => $uid,
                            'language' => DEFAULT_LANGUAGE,
                        ],
                    ]);

                    if (!empty($text)) {
                        $text = $text->duplicate();
                        $text->setLanguage($_SESSION['frontend']['language']);
                        $text->save();
                    }
                }

                if (empty($text)) {

                    $className = $this->class;

                    $text = $this->insert(new $className([
                        'uid' => $uid,
                        'userId' => $_SESSION['user']['id'],
                        'language' => $_SESSION['frontend']['language'],
                    ]));
                }
            }

            if (!empty($options['fallbackLanguageDefault'])) {

                $text = $this->fetchOne([
                    'where' => [
                        'uid' => $uid,
                        'language' => DEFAULT_LANGUAGE,
                    ],
                ]);
            }
        }

        return $text;
    }

    /**
     *
     */
    public function fetchByUidBase(string $uidBase)
    {
        return $this->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\Like('uid', $uidBase . '%')
            ]
        ]);
    }

    /**
     *
     */
    public function fetchResultByUid($uid, array $parameters = null): \Frootbox\Db\Result
    {
        if (empty($parameters['order'])) {
            $parameters['order'] = 'date DESC';
        }

        // Fetch text
        $result = $this->fetch([
            'where' => [ 'uid' => $uid ],
            'order' => [ $parameters['order'] ],
        ]);

        return $result;
    }
}
