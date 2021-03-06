<?php

namespace LoginAttempts\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\I18n\Time;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use LoginAttempts\Model\Entity\Attempt;

/**
 * Attempts Model
 *
 * @method Attempt newEntity($data = null, array $options = [])
 * @method Attempt[] newEntities(array $data, array $options = [])
 * @method Attempt patchEntity(EntityInterface $entity, array $data, array $options = [])
 * @method Attempt[] patchEntities($entities, array $data, array $options = [])
 * @method Attempt get($primaryKey, $options = [])
 * @method Attempt|bool save(EntityInterface $entity, $options = [])
 */
class AttemptsTable extends Table implements AttemptsTableInterface
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->setTable('attempts');
        $this->setDisplayField('ip');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created_at' => 'new',
                ]
            ],
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param Validator $validator Validator instance.
     * @return Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->add('id', 'valid', ['rule' => 'numeric'])
            ->allowEmpty('id', 'create');

        $validator
            ->requirePresence('ip', 'create')
            ->notEmpty('ip')
            ->add('ip', 'ip', [
                'rule' => 'ip',
                'message' => __d('login_attemts', 'invalid IP address'),
            ]);

        $validator
            ->requirePresence('action', 'create')
            ->notEmpty('action');

        $validator
            ->requirePresence('expires', 'create')
            ->notEmpty('expires');

        $validator
            ->requirePresence('created_at', 'create')
            ->notEmpty('created_at');

        return $validator;
    }

    /**
     * record on login failed
     *
     * @param string $ip A request client ip.
     * @param string $action A request target action.
     * @param string $duration Duration to disable login.
     * @return bool
     */
    public function fail($ip, $action, $duration)
    {
        $attempt = $this->newEntity([
            'ip' => $ip,
            'action' => $action,
            'expires' => Time::parse($duration),
            'created_at' => Time::now(),
        ]);

        return $this->save($attempt);
    }

    /**
     * check attempts less than $limit
     *
     * @param string $ip A request client ip.
     * @param string $action A request target action.
     * @param int $limit Number of trial limitation.
     * @return bool
     */
    public function check($ip, $action, $limit)
    {
        $count = $this->find()->where([
                'ip' => $ip,
                'action' => $action,
                'expires >=' => Time::now(),
            ])->count();

        return $count < $limit;
    }

    /**
     * reset on login success
     *
     * @param string $ip A request client ip.
     * @param string $action A request target action.
     * @return bool
     */
    public function reset($ip, $action)
    {
        return $this->deleteAll([
                'ip' => $ip,
                'action' => $action,
        ]);
    }

    /**
     * cleanup expired data
     *
     * @return bool
     */
    public function cleanup()
    {
        return $this->deleteAll([
                'expires <' => Time::now(),
        ]);
    }
}
