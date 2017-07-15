/**
 * The external dependencies.
 */
import { take, call, put, fork, select } from 'redux-saga/effects';
import { isEmpty, isNull, mapValues, defaultTo } from 'lodash';

/**
 * The internal dependencies.
 */
import { ready } from 'lib/actions';
import { createSelectboxChannel, createCheckableChannel, createSubmitChannel, createTextChangeChannel } from 'lib/events';

import { getContainersByType } from 'containers/selectors';
import { validateAllContainers, submitForm, setContainerMeta } from 'containers/actions';
import { TYPE_POST_META } from 'containers/constants';

/**
 * Dispatch the action that will update the store.
 *
 * @param  {Object} containers
 * @param  {Object} meta
 * @return {void}
 */
function* syncStore(containers, meta) {
	yield put(setContainerMeta(mapValues(containers, () => meta)));
}

/**
 * Keep in sync the `post_template` property.
 *
 * @param  {Object} containers
 * @return {void}
 */
export function* workerSyncPostTemplate(containers) {
	const channel = yield call(createSelectboxChannel, 'select#page_template');

	while (true) {
		const { value } = yield take(channel);

		yield call(syncStore, containers, {
			post_template: value,
		});
	}
}

/**
 * Keep in sync the `post_parent_id` & `post_level` properties.
 *
 * @param  {Object} containers
 * @return {void}
 */
export function* workerSyncPostParentId(containers) {
	const channel = yield call(createSelectboxChannel, 'select#parent_id');

	while (true) {
		const { value, option } = yield take(channel);
		const parentId = defaultTo(parseInt(value, 10), 0);
		let level = 1;

		if (option.className) {
			const matches = option.className.match(/^level-(\d+)/);

			if (matches) {
				level = parseInt(matches[1], 10) + 2;
			}
		}

		yield call(syncStore, containers, {
			post_parent_id: parentId,
			post_level: level,
		});
	}
}

/**
 * Keep in sync the `post_format` property.
 *
 * @param  {Object} containers
 * @return {void}
 */
export function* workerSyncPostFormat(containers) {
	const channel = yield call(createCheckableChannel, '#post-formats-select');

	while (true) {
		const { values } = yield take(channel);

		yield call(syncStore, containers, {
			post_format: isNull(values[0]) ? '' : values[0],
		});
	}
}

/**
 * Setup the workers for different terms.
 *
 * @param  {Object}   containers
 * @param  {String}   selector
 * @param  {Function} worker
 * @return {void}
 */
function* setupSyncTerms(containers, selector, worker) {
	const elements = document.querySelectorAll(`div[id^="${selector}"]`);

	for (const element of elements) {
		yield fork(worker, containers, element.id.replace(selector, ''));
	}
}

/**
 * Keep in sync the hierarchical terms(e.g categories).
 *
 * @param  {Object} containers
 * @param  {String} taxonomy
 * @return {void}
 */
export function* workerSyncHierarchicalTerms(containers, taxonomy) {
	const channel = yield call(createCheckableChannel, `#${taxonomy}checklist`);

	while (true) {
		const { values } = yield take(channel);

		yield call(syncStore, containers, {
			post_term: {
				[taxonomy]: values.map(value => parseInt(value, 10))
			},
		});
	}
}

/**
 * Keep in sync the non-hierarchical terms(e.g tags).
 *
 * @param  {Object} containers
 * @param  {String} taxonomy
 * @return {void}
 */
export function* workerSyncNonHierarchicalTerms(containers, taxonomy) {
	const channel = yield call(createTextChangeChannel, `#${taxonomy} .the-tags`);

	while (true) {
		const { value } = yield take(channel);

		yield call(syncStore, containers, {
			post_term: {
				[taxonomy]: value ? value.split(/,\s*/) : [],
			},
		});
	}
}

/**
 * Handle the form submission.
 *
 * @return {void}
 */
export function* workerFormSubmit() {
	const channel = yield call(createSubmitChannel, ':not(.comment-php) form#post');

	while (true) {
		const { event } = yield take(channel);

		yield put(submitForm(event));
		yield put(validateAllContainers(event));
	}
}

/**
 * Start to work.
 *
 * @param  {Object} store
 * @return {void}
 */
export default function* foreman() {
	const containers = yield select(getContainersByType, TYPE_POST_META);

	// Nothing to do.
	if (isEmpty(containers)) {
		return;
	}

	// Block and wait for a `READY` event.
	yield take(ready);

	// Start the workers.
	yield fork(workerSyncPostTemplate, containers);
	yield fork(workerSyncPostParentId, containers);
	yield fork(workerSyncPostFormat, containers);
	yield fork(setupSyncTerms, containers, 'taxonomy-', workerSyncHierarchicalTerms);
	yield fork(setupSyncTerms, containers, 'tagsdiv-', workerSyncNonHierarchicalTerms);
	yield fork(workerFormSubmit);
}
