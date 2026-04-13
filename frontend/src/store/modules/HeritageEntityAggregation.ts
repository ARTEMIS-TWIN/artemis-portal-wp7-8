import { HeritageEntitySearchModule } from './HeritageEntitySearch';
import { descriptions, order, resultTitles, titles } from './HeritageEntityAggregationStatic';
import utils from '@/utils/utils';

export interface heritageFilter {
  key: string,
  val: string,
}

export class HeritageEntityAggregationModule {
  searchModule: HeritageEntitySearchModule;
  titles: any = titles;
  descriptions: any = descriptions;
  resultTitles: any = resultTitles;
  order: string[] = order;

  constructor(searchModule: HeritageEntitySearchModule) {
    this.searchModule = searchModule;
  }

  setActive(payload: any) {
    const params = this.searchModule.getParams;
    let { key, value, add } = payload;

    if (params[key]) {
      let values = params[key].split('|');

      if (add) {
        values.push(value);
      } else {
        values = values.filter((item: string) => String(item || '').toLowerCase() !== String(value || '').toLowerCase());
      }

      value = values.join('|');
    }

    this.searchModule.setSearch({
      [key]: value,
      page: 0,
    });
  }

  get getTitle() {
    return (key: string) => this.titles[key] || utils.sentenceCase(utils.splitCase(key));
  }

  get getDescription() {
    return (key: string) => this.descriptions[key] || '';
  }

  get getResultTitle() {
    return (key: string) => this.resultTitles[key] || utils.sentenceCase(utils.splitCase(key));
  }

  get getIsActive() {
    return (key: string, bucketKey: string) => {
      const params = this.searchModule.getParams;

      if (params[key]) {
        return params[key].split('|').some((item: string) => String(item || '').toLowerCase() === String(bucketKey || '').toLowerCase());
      }

      return false;
    };
  }

  get getSorted() {
    const aggs = this.searchModule.getAggsResult?.aggs || {};
    const result: any = {};

    this.order.forEach((key: string) => {
      if (aggs[key]) {
        result[key] = aggs[key];
      }
    });

    return result;
  }

  get activeFilters(): heritageFilter[] {
    const params = utils.getCopy(this.searchModule.getParams);
    const filters: heritageFilter[] = [];
    const valid = ['q', ...this.order];

    valid.forEach((key: string) => {
      if (typeof params[key] === 'string' && params[key].trim()) {
        params[key].split('|').forEach((value: string) => filters.push({ key, val: value }));
      }
    });

    return filters;
  }
}
