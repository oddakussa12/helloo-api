<?php

/*
|--------------------------------------------------------------------------
| Notifynder Configuration
|--------------------------------------------------------------------------
*/

return [

    /*
     * If you have a different user model
     * please specific it here, this option is not
     * considerate if using notifynder as polymorphic
     */
    'model' => 'App\Models\User',

    /*
     * Do you want notifynder to eager load its related models?
     * just swap the value to true to eager load relations
     * or you can specify which relations to eager load
     *
     * Possible Values:
     * false: No relations are eager loaded (default)
     * true: All relations are eager loaded
     * array: Specify which relations to eager load can be any combination of ['category', 'from', 'to']
     */
    'eager_load' => false,

    /*
     * Do you want have notifynder that work polymorphically?
     * just swap the value to true and you will able to use it!
     */
    'polymorphic' => false,

    /*
     * Coordinating a lots notifications that require extra params
     * might cause to forget and not insert the {extra.*} value needed.
     * This flag allow you to cause an exception to be thrown if you miss
     * to store a extra param that the category will need.
     * NOTE: use only in development.
     * WHEN DISABLED: will just remove the {extra.*} markup from the sentence
     */
    'strict_extra' => false,

    /*
     * If you wish to have the translations in a specific file
     * just require the file on the following option.
     *
     * To get started with the translations just reference a key with
     * the language you wish to translate ex 'it' or 'italian' and pass as
     * value an array with the translations
     */
    'translation'  => [
        'enabled' => true,
        'domain' => 'notifynder',
    ],

    /*
     * If you have added your own fields to the Notification Model
     * you can add them to the arrays below.
     *
     * If you want them to be required by the builder add them to the
     * to the required key - if they are just added you can add them
     * to the fillable key.
     */
    'additional_fields' => [
        'required' => [
        ],
        'fillable' => [
            'contact_id'
        ],
    ],
];
