import template from './act-quote-document-info.html.twig';
import './act-quote-document-info.scss';

const { Component } = Shopware;

const HOW_TO_ITEM_COUNT = 5;

Component.register('act-quote-document-info', {
    template,

    computed: {
        howToItems() {
            return Array.from(
                { length: HOW_TO_ITEM_COUNT },
                (_, index) => `act-quote-document.settings.info.howTo.item${index + 1}`,
            );
        },
    },
});
