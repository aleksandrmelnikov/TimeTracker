import $ from "jquery";
import { ApiTimeEntry, TimeEntryApi, TimeEntryApiErrorCode } from "../core/api/time_entry_api";
import Observable from "./observable";
import { ConfirmClickEvent, ConfirmDialog } from "./confirm_dialog";
import { ApiErrorResponse, ApiResourceError } from "../core/api/api";
import TimerView from "./timer";

abstract class SyncInputInternal {
    private $input: JQuery;
    private $loadingContainer: JQuery;
    private debounceTime = 500;
    private timeout: any;

    /**
     * textChanged is triggered whenever the text has been changed in the input, after the debounce time.
     */
    public readonly textChanged = new Observable<string>();

    /**
     * textUplaoded is triggered whenever the text change has been successfully uploaded the the server.
     */
    public readonly textUploaded = new Observable<string>();

    constructor(
        $inputElement: JQuery,
        $loadingElement: JQuery) {
        this.$input = $inputElement;
        this.$loadingContainer = $loadingElement
    }

    protected abstract update(text: string): Promise<any>;

    private startLoading() {
        this.$loadingContainer.find('.js-loading').removeClass('d-none');
    }

    private stopLoading() {
        this.$loadingContainer.find('.js-loading').addClass('d-none');
    }

    protected onTextChange(text: string): Promise<any> {
        this.textChanged.emit(text);

        return this.update(text)
            .then(() => {
                this.stopLoading();
                this.textUploaded.emit(text);
            });
    }

    start() {
        this.$input.on('input propertychange', () => {
            clearTimeout(this.timeout);
            this.startLoading();

            this.timeout = setTimeout(() => {
                const text = this.$input.val() as string;

                this.onTextChange(text);
            }, this.debounceTime);
        })

    }

    stop() {
        clearTimeout()
        this.$input.off('input propertychange');
    }

    setDebounceTime(value: number) {
        this.debounceTime = value;
    }

    upload() {
        this.startLoading();
        const text = this.$input.val() as string;
        return this.onTextChange(text);
    }

    uploadIfHasText(): Promise<any> {
        const descriptionText = this.$input.val() as string;
        if (descriptionText && descriptionText.length > 0) {
            return this.upload();
        }

        return Promise.resolve();
    }
}

export class SyncTaskTimeEntryDescription extends SyncInputInternal {
    private timeEntryId?: string;

    public constructor(
        $inputElement: JQuery,
        $loadingElement: JQuery) {
        super($inputElement, $loadingElement);
    }

    protected update(text: string): Promise<any> {
        if (!this.timeEntryId) {
            return Promise.resolve();
        }

        return TimeEntryApi.update(this.timeEntryId, {
            description: text
        });
    }

    setTimeEntryId(id: string) {
        this.timeEntryId = id;
    }

    clearTimeEntry() {
        this.timeEntryId = undefined;
    }
}

export enum TimeEntryState {
    created,  // the object has been created, but nothing set yet.
    initializing,  // the object is awaiting data
    failedToInitialize, // the object failed to get data
    notRunning, // this is the state after a successful stop, because
    starting, // attempting to start
    failedToStart, // failed to start
    running, // the time entry is currently active and running
    stopping, // attempting to stop
    failedToStop// failed to stop
}

export default class TaskTimeEntry {
    private readonly taskId: string;
    private durationFormat = '%Hh:%Im:%Ss';
    private $container: JQuery;
    private $description: JQuery;
    private $button: JQuery;
    private $loading: JQuery;

    private descriptionUpdater: SyncTaskTimeEntryDescription;

    private durationTimer: TimerView;
    private model?: ApiTimeEntry;
    public readonly stopped = new Observable<ApiTimeEntry>();
    private _state: TimeEntryState;
    private get state(): TimeEntryState {
        return this._state;
    }
    private set state(state: TimeEntryState) {
        this._state = state;

        switch (state) {
            case TimeEntryState.starting:
                this.startLoading();
                break;
            case TimeEntryState.stopping:
                this.startLoading();
                break;
            case TimeEntryState.running:
                if (!this.model) {
                    throw new Error('Model is not defined');
                }

                this.descriptionUpdater.setTimeEntryId(this.model.id);
                this.descriptionUpdater.start();
                this.descriptionUpdater.uploadIfHasText();

                this.stopLoading();
                this.setButtonToStop();
                this.durationTimer.startedAt = this.model.startedAtEpoch * 1000;
                this.durationTimer.start();
                break
            case TimeEntryState.notRunning:
                this.stopLoading();
                this.setButtonToStart();
                this.durationTimer.stop();
                this.clearDescription();
                this.clearDuration();
                this.descriptionUpdater.stop();
                this.descriptionUpdater.uploadIfHasText()
                    .then(() => {
                        this.descriptionUpdater.clearTimeEntry();
                    });

                break;
        }
    }


    public constructor(taskId: string) {
        this.taskId = taskId;
        this.state = TimeEntryState.created;

        this.$container = $('.js-task-time-entry');
        this.$description = this.$container.find('.js-content .js-time-entry-description');

        this.$button = this.$container.find('.js-content button');
        this.$button.on('click', () => {
            if (this.state === TimeEntryState.running) {
                this.stop();
            } else if (this.state === TimeEntryState.notRunning) {
                this.start();
            }
        });

        this.$loading = this.$container.find('.js-task-time-entry-loading');

        this.durationTimer = new TimerView($('.js-duration'), this.durationFormat);

        this.descriptionUpdater = new SyncTaskTimeEntryDescription(
            $('.js-task-time-entry .js-time-entry-description'),
            $('.js-task-time-entry'),
        );
    }

    public initialize() {
        this.state = TimeEntryState.initializing;

        TimeEntryApi.getActive()
            .then(res => {
                if (res.data) {
                    this.setData(res.data);
                } else {
                    this.state = TimeEntryState.notRunning;
                }
            });
    }

    public setDurationFormat(value: string) {
        this.durationFormat = value;
        this.durationTimer.setDurationFormat(value);
    }

    private setData(model: ApiTimeEntry) {
        if (model && model.taskId === this.taskId) {
            this.model = model;

            if (this.model.endedAt) {
                this.state = TimeEntryState.notRunning;
            } else {
                this.state = TimeEntryState.running;
                this.setDescription(model.description);
            }
        } else {
            this.state = TimeEntryState.notRunning;
        }
    }

    private startLoading() {
        this.$loading.removeClass('d-none');
    }

    private stopLoading() {
        this.$loading.addClass('d-none');
    }

    private setButtonToStart() {
        this.$button.removeClass('btn-danger');
        this.$button.addClass('btn-primary');
        this.$button.find('.js-text').text('Start');
    }

    private setButtonToStop() {
        this.$button.removeClass('btn-primary');
        this.$button.addClass('btn-danger');
        this.$button.find('.js-text').text('Stop');
    }

    private setDescription(text: string) {
        this.$description.val(text);
    }

    private clearDescription() {
        this.setDescription('');
    }

    private clearDuration() {
        $('.js-duration').text(this.durationTimer.getZeroDurationString());
    }

    private confirmStopRunningTimeEntry(timeEntryId: string) {
        const confirmDialog = new ConfirmDialog('btn-danger');
        confirmDialog.clicked.addObserver((event: ConfirmClickEvent) => {
            if (event.buttonClicked === 'confirm') {
                TimeEntryApi.stop(timeEntryId)
                    .then(res => {
                        this.tryCreatingTimeEntry();
                        confirmDialog.remove();
                    })
            } else {
                this.state = TimeEntryState.notRunning;
            }
        });

        confirmDialog.show({
            title: 'Stop running time entry?',
            body: 'You have a running time entry, stop it and start a new one?',
            cancelText: 'close',
            confirmText: 'stop'
        });
    }

    private tryCreatingTimeEntry() {
        TimeEntryApi.create({
            taskId: this.taskId,
            }).then(res => {
                this.model = res.data.timeEntry;
                this.state = TimeEntryState.running;
            }).catch( (res: ApiErrorResponse) => {
                if (res.hasErrorCode(TimeEntryApiErrorCode.codeRunningTimer)) {
                    const error = res.getErrorForCode(TimeEntryApiErrorCode.codeRunningTimer) as ApiResourceError;
                    const timeEntryId = error.resource;
                    this.confirmStopRunningTimeEntry(timeEntryId);
                }
            }
        );
    }

    public start() {
        if (this.state !== TimeEntryState.notRunning) {
            return;
        }

        this.state = TimeEntryState.starting;

        this.tryCreatingTimeEntry();
    }

    public stop() {
        if (this.state !== TimeEntryState.running) {
            return;
        }

        this.state = TimeEntryState.stopping;

        if (!this.model) {
            throw new Error('Model is not defined');
        }

        TimeEntryApi.stop(this.model.id)
            .then(res => {
                this.state = TimeEntryState.notRunning;
                this.stopped.emit(res.data);
            })
    }
}