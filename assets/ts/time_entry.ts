import '../styles/time_entry.scss';

import $ from 'jquery';
import 'bootstrap'; // Adds functions to jQuery

import { TimeEntryApi } from "./core/api/time_entry_api";
import Flashes from "./components/flashes";
import AutoMarkdown from "./components/automarkdown";
import TagList from "./components/tag_index";
import { TimeEntryTaskAssigner } from "./components/time_entry_task_assigner";
import { TimeEntryApiAdapter } from "./components/time_entry_api_adapater";
import { TagAssigner } from "./components/tag_assigner";
import TimerView from "./components/timer";
import StatisticValueList, { AddStatisticValue, StatisticValueListDelegate } from "./components/statistic_value_list";
import { ApiErrorResponse, JsonResponse } from "./core/api/api";
import StatisticValuePicker, { StatisticValuePickedEvent } from "./components/statistic_value_picker";
import { ApiStatisticValue, StatisticValueApi } from "./core/api/statistic_value_api";

class TimeEntryAutoMarkdown extends AutoMarkdown {
    private readonly timeEntryId: string;

    constructor(
        inputSelector: string,
        markdownSelector: string,
        loadingSelector: string,
        timeEntryId: string) {
        super(inputSelector, markdownSelector, loadingSelector);
        this.timeEntryId = timeEntryId;
    }

    protected update(body: string): Promise<any> {
        return TimeEntryApi.update(this.timeEntryId, {
            description: body,
        });
    }
}

class TimeEntryStatisticDelegate implements StatisticValueListDelegate{
    constructor(private timeEntryId: string) {
    }

    add(value: AddStatisticValue): Promise<JsonResponse<ApiStatisticValue>> {
        return TimeEntryApi.addStatistic(this.timeEntryId, {
            statisticName: value.name,
            value: value.value
        });
    }

    update(id: string, value: number): Promise<JsonResponse<ApiStatisticValue>> {
        return StatisticValueApi.update(id, value);
    }

    remove(id: string): Promise<JsonResponse<void>> {
        return TimeEntryApi.removeStatistic(this.timeEntryId, id);
    }
}

class TimeEntryPage {
    private readonly timeEntryId: string;
    private readonly durationFormat: string;
    private autoMarkdown: TimeEntryAutoMarkdown;
    private autocompleteTask: TimeEntryTaskAssigner;
    private tagEdit: TagAssigner;
    private readonly flashes: Flashes;
    private timerView?: TimerView;

    constructor() {
        const $data = $('.js-data');
        this.timeEntryId = $data.data('time-entry-id');
        this.durationFormat = $data.data('duration-format');
        this.flashes = new Flashes($('#fixed-flash-messages'));

        this.autoMarkdown = new TimeEntryAutoMarkdown(
            '.js-description',
            '#preview-content',
            '.markdown-link',
            this.timeEntryId
        );

        this.autocompleteTask = new TimeEntryTaskAssigner($('.js-autocomplete-task'), this.timeEntryId, this.flashes);

        const $tagList = $('.js-tags');
        const tagList = new TagList($tagList, new TimeEntryApiAdapter(this.timeEntryId, this.flashes));
        const $template = $('.js-autocomplete-tags-container');

        this.tagEdit = new TagAssigner($template, tagList, this.flashes);

        const $timer = $('.js-timer.js-running-timer');
        if ($timer.length !== 0) {
            this.timerView = new TimerView($timer, (durationString) => {
                document.title = durationString;
            });

            this.timerView.start();
        }

        this.addStatisticData();
    }

    private addStatisticData() {
        const statisticValueList = new StatisticValueList($('.statistic-values'), new TimeEntryStatisticDelegate(this.timeEntryId), this.flashes);

        const statisticValuePicker = new StatisticValuePicker($('.js-add-statistic'), 'interval');
        statisticValuePicker.valuePicked.addObserver(async (event: StatisticValuePickedEvent) => {
            try {
                await statisticValueList.add({
                    name: event.name,
                    value: event.value
                });
            } catch (e) {
                if (!(e instanceof ApiErrorResponse)) {
                    throw e;
                }

                const err = e as ApiErrorResponse;

                if (err.response.status === 409) {
                    this.flashes.append('danger', `Unable to add record, a record with name '${event.name}' already exists for ${event.day}`);
                } else {
                    this.flashes.append('danger', 'Unable to add record');
                }
            }
        });
    }
}

$(document).ready(() => {
    const page = new TimeEntryPage();
});