import { helpers, email, maxLength, required } from '@vuelidate/validators';

function booleanPresence(value) {
    return typeof value === 'boolean';
}

function translate(translator, key, replacements = {}) {
    return typeof translator === 'function' ? translator(key, replacements) : key;
}

function requiredMessage(translator) {
    return helpers.withMessage(
        () => translate(translator, 'Validation required'),
        required,
    );
}

function booleanMessage(translator) {
    return helpers.withMessage(
        () => translate(translator, 'Validation boolean'),
        booleanPresence,
    );
}

function emailMessage(translator) {
    return helpers.withMessage(
        () => translate(translator, 'Validation email'),
        email,
    );
}

function maxMessage(max, translator) {
    return helpers.withMessage(
        () => translate(translator, 'Validation max characters', { max }),
        maxLength(max),
    );
}

function mapFieldRules(config, translator) {
    const rules = {};
    const types = Array.isArray(config.types) ? config.types : [];

    if (config.required === true && !types.includes('boolean')) {
        rules.required = requiredMessage(translator);
    }

    if (types.includes('boolean')) {
        rules.boolean = booleanMessage(translator);
    }

    if (config.format === 'email') {
        rules.email = emailMessage(translator);
    }

    if (typeof config.max === 'number') {
        rules.maxLength = maxMessage(config.max, translator);
    }

    return rules;
}

export function buildVuelidateRules(schema, options = {}) {
    const fields = schema?.fields ?? {};

    return Object.fromEntries(
        Object.entries(fields).map(([field, config]) => [
            field,
            mapFieldRules(config, options.translator),
        ]),
    );
}
