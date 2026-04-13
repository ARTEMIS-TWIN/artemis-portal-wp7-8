import axios from 'axios';
import router from '@/router';
import utils from '@/utils/utils';
import { LoadingStatus, GeneralModule } from './General';
import { perPageOptions, sortOptions } from './HeritageEntitySearchStatic';

export class HeritageEntitySearchModule {
  generalModule: GeneralModule;
  params: any = {};
  result: any = {};
  aggsResult: any = {};
  totalCount: string = '0';
  reqMap: any = { hits: 0, aggs: 0 };

  sortOptions: any[] = sortOptions;
  perPageOptions: any[] = perPageOptions;

  constructor(generalModule: GeneralModule) {
    this.generalModule = generalModule;
  }

  async setSearch(payload: any) {
    let currentPath = router.currentRoute.value.path;
    currentPath = currentPath.endsWith('/') ? currentPath.slice(0, -1) : currentPath;

    let path = payload.path ?? currentPath;
    let params: any = utils.getCopy(this.params);
    const currentParams = utils.getCopy(params);
    let updateUrl = true;

    if (payload.fromRoute) {
      updateUrl = false;
      params = {};
      payload = {};

      const urlParams = new URLSearchParams(location.search);
      urlParams.forEach((val: string, key: string) => payload[key] = val);
    }

    for (const key in payload) {
      if (payload[key] && key !== 'path' && key !== 'fromRoute' && key !== 'forceReload') {
        params[key] = payload[key];
      } else if (!['path', 'fromRoute', 'forceReload'].includes(key)) {
        delete params[key];
      }
    }

    if (params.page) {
      params.page = parseInt(params.page);
    }

    if (!params.q) {
      params.q = '';
    }

    if (utils.objectEquals(params, currentParams) && path === currentPath && !payload.forceReload) {
      return;
    }

    if (updateUrl) {
      const stringParams = utils.objectConvertNumbersToStrings(params);

      if (!utils.objectEquals(router.currentRoute.value.query, stringParams) || path !== currentPath) {
        router.push(utils.paramsToString(path, params));
      }
    }

    const reqId = ++this.reqMap.hits;
    const time = Date.now();

    this.generalModule.updateLoadingStatus(LoadingStatus.Locked);
    this.params = params;

    let data: any = null;

    try {
      const url = process.env.apiUrl + '/heritage-entities/search';
      const res = await axios.get(utils.paramsToString(url, { ...params, ...this.getDefaultSort() }));
      data = res?.data;
    } catch (ex) {}

    if (reqId !== this.reqMap.hits) {
      return;
    }

    if (data && !data.error) {
      this.result = {
        total: data.total,
        hits: data.hits,
        time: Math.round(((Date.now() - time) / 1000) * 100) / 100,
        aggs: data.aggregations,
      };
    } else {
      this.result = { error: 'Internal error. Search failed.' };
    }

    this.generalModule.updateLoadingStatus(LoadingStatus.None);
  }

  async setAggregationSearch(routerQuery: any) {
    const reqId = ++this.reqMap.aggs;

    try {
      const url = process.env.apiUrl + '/heritage-entities/aggregations';
      const res = await axios.get(utils.paramsToString(url, { ...routerQuery, ...this.getDefaultSort() }));

      if (reqId === this.reqMap.aggs) {
        this.aggsResult = {
          total: res?.data?.total,
          hits: res?.data?.hits,
          aggs: res?.data?.aggregations || {},
        };
      }
    } catch (ex) {
      if (reqId === this.reqMap.aggs) {
        this.aggsResult = { error: 'Internal error. Aggregations failed.' };
      }
    }
  }

  async setTotalCount() {
    try {
      const res = await axios.get(process.env.apiUrl + '/heritage-entities/count');
      this.totalCount = String(res?.data ?? '0');
    } catch (ex) {
      this.totalCount = '0';
    }
  }

  actionResetResultState() {
    this.result = {};
    this.aggsResult = {};
    this.params = {};
  }

  get getDefaultSort(): any {
    return () => {
      if (this.params.sort && this.params.order) {
        return {
          sort: this.params.sort,
          order: this.params.order,
        };
      }

      if (String(this.params.q || '').trim()) {
        return {
          sort: '_score',
          order: 'desc',
        };
      }

      return {
        sort: 'label',
        order: 'asc',
      };
    };
  }

  get getParams(): any {
    return this.params;
  }

  get getResult(): any {
    return this.result;
  }

  get getAggsResult(): any {
    return this.aggsResult;
  }

  get getPerPage(): string {
    return this.params?.size || '20';
  }

  get getPerPageOptions(): any[] {
    return this.perPageOptions;
  }

  get getSortOptions(): any[] {
    return this.sortOptions;
  }

  get getTotalCount(): string {
    return new Intl.NumberFormat('en', { style: 'decimal' }).format(parseInt(this.totalCount || '0'));
  }
}
