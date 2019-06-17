<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2017 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */

namespace BEdita\Core\Model\Validation;

/**
 * Validator for profiles.
 *
 * @since 4.0.0
 */
class ProfilesValidator extends ObjectsValidator
{

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $this
            ->allowEmpty('name')

            ->allowEmpty('surname')

            ->email('email')
            ->allowEmpty('email')

            ->allowEmpty('person_title')

            ->allowEmpty('gender')

            ->add('birthdate', 'date', ['rule' => [Validation::class, 'dateTime']])
            ->allowEmpty('birthdate')

            ->add('deathdate', 'date', ['rule' => [Validation::class, 'dateTime']])
            ->allowEmpty('deathdate')

            ->boolean('company')
            ->allowEmpty('company')

            ->allowEmpty('company_name')

            ->allowEmpty('company_kind')

            ->allowEmpty('street_address')

            ->allowEmpty('city')

            ->allowEmpty('zipcode')

            ->allowEmpty('country')

            ->allowEmpty('state_name')

            ->allowEmpty('phone')

            // Use `add` instead of `urlWithProtocol` to preserve rule name.
            ->add('website', 'url', [
                'rule' => ['url', true],
            ])
            ->allowEmpty('website')

            ->allowEmpty('national_id_number')

            ->allowEmpty('vat_number');
    }
}
