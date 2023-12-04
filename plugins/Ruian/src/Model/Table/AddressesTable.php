<?php
declare(strict_types=1);

namespace Ruian\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Addresses Model
 *
 * @method \Ruian\Model\Entity\Address newEmptyEntity()
 * @method \Ruian\Model\Entity\Address newEntity(array $data, array $options = [])
 * @method \Ruian\Model\Entity\Address[] newEntities(array $data, array $options = [])
 * @method \Ruian\Model\Entity\Address get(mixed $primaryKey, array|string $finder = 'all', null|\Psr\SimpleCache\CacheInterface|string $cache = null, null|\Closure|string $cacheKey = null, mixed ...$args)
 * @method \Ruian\Model\Entity\Address findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Ruian\Model\Entity\Address patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Ruian\Model\Entity\Address[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Ruian\Model\Entity\Address|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Ruian\Model\Entity\Address saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method iterable<\Ruian\Model\Entity\Address>|false saveMany(iterable $entities, $options = [])
 * @method iterable<\Ruian\Model\Entity\Address> saveManyOrFail(iterable $entities, $options = [])
 * @method iterable<\Ruian\Model\Entity\Address>|false deleteMany(iterable $entities, $options = [])
 * @method iterable<\Ruian\Model\Entity\Address> deleteManyOrFail(iterable $entities, $options = [])
 */
class AddressesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('addresses');
        $this->setDisplayField('kod_adm');
        $this->setPrimaryKey('kod_adm');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('kod_adm')
            ->allowEmptyString('kod_adm', null, 'create');

        $validator
            ->integer('obec_kod')
            ->allowEmptyString('obec_kod');

        $validator
            ->scalar('obec_nazev')
            ->allowEmptyString('obec_nazev');

        $validator
            ->integer('momc_kod')
            ->allowEmptyString('momc_kod');

        $validator
            ->scalar('momc_nazev')
            ->allowEmptyString('momc_nazev');

        $validator
            ->integer('mop_kod')
            ->allowEmptyString('mop_kod');

        $validator
            ->scalar('mop_nazev')
            ->allowEmptyString('mop_nazev');

        $validator
            ->integer('cast_obce_kod')
            ->allowEmptyString('cast_obce_kod');

        $validator
            ->scalar('cast_obce_nazev')
            ->allowEmptyString('cast_obce_nazev');

        $validator
            ->integer('ulice_kod')
            ->allowEmptyString('ulice_kod');

        $validator
            ->scalar('ulice_nazev')
            ->allowEmptyString('ulice_nazev');

        $validator
            ->scalar('typ_so')
            ->allowEmptyString('typ_so');

        $validator
            ->integer('cislo_domovni')
            ->allowEmptyString('cislo_domovni');

        $validator
            ->integer('cislo_orientacni')
            ->allowEmptyString('cislo_orientacni');

        $validator
            ->scalar('cislo_orientacni_znak')
            ->allowEmptyString('cislo_orientacni_znak');

        $validator
            ->integer('psc')
            ->allowEmptyString('psc');

        $validator
            ->date('plati_od')
            ->allowEmptyDate('plati_od');

        $validator
            ->scalar('geometry')
            ->allowEmptyString('geometry');

        $validator
            ->scalar('geometry_jtsk')
            ->allowEmptyString('geometry_jtsk');

        return $validator;
    }

    /**
     * Returns the database connection name to use by default.
     *
     * @return string
     */
    public static function defaultConnectionName(): string
    {
        return 'ruian';
    }
}
