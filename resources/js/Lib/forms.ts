import type { KeyboardEvent } from 'react';

/**
 * Swallows Enter inside a form so it can't implicitly submit.
 *
 * Every backoffice CRUD form lives in a modal whose submit handler closes the
 * modal on success (`onSuccess: () => closeModal()`), so an accidental Enter
 * saved the record and dismissed the modal mid-edit. That is very easy to hit
 * on the fee tables, where a row is a strip of small number inputs (montant,
 * remise %, remise DH) the user tabs and types through — browsers submit a
 * form on Enter from any single-line control.
 *
 * Only the explicit submit button should submit. Attach as:
 * `<form onSubmit={submit} onKeyDown={blockImplicitSubmit}>`.
 *
 * Textareas keep their normal Enter (newline) behaviour, buttons keep theirs
 * (Enter is their activation key, so the real submit button still works), and
 * any control can opt back in with a `data-allow-enter` attribute.
 */
export function blockImplicitSubmit(event: KeyboardEvent<HTMLFormElement>): void {
    if (event.key !== 'Enter') {
        return;
    }

    const target = event.target as HTMLElement;

    if (target.tagName === 'TEXTAREA' || target.dataset.allowEnter !== undefined) {
        return;
    }

    if (target.tagName === 'BUTTON' || (target as HTMLInputElement).type === 'submit') {
        return;
    }

    event.preventDefault();
}
