import { createRouter, createWebHistory } from 'vue-router';
import { generalModule, resourceModule } from "@/store/modules";
import { breadCrumbModule } from "@/store/modules";

// components
import About from './component/About.vue';
import ArtemisIA from './component/ArtemisIA.vue';
import NotFound from './component/NotFound.vue';
import Resource from './component/Resource.vue';
import Subject from './component/Subject.vue';
import Publisher from './component/Publisher.vue';
import Result from './component/Result.vue';
import Services from './component/Services.vue';
import ServiceDetail from './component/ServiceDetail.vue';
import Theme from './component/Theme.vue';
import Infographic from './component/Infographic.vue';
import Guide from './component/Guide.vue';
import HeritageEntities from './component/HeritageEntities.vue';
import HeritageEntityDetail from './component/HeritageEntityDetail.vue';
//import MaintenancePage from './component/MaintenancePage.vue';

/**
 * Router - component url paths
 */

const router = createRouter({
  history: createWebHistory(process.env.ARIADNE_PUBLIC_PATH),
  routes: [
    /*  Maintenance mode */
    // {
    //   path: '/:pathMatch(.*)*',
    //   component: MaintenancePage
    // },
    {
      path: '/',
      component: Result,
      meta: {
        title: 'Data Resources',
        description: 'Search and explore the ARTEMIS data resources.',
      }
    },
    {
      path: '/search',
      component: Result
    },
    {
      path: '/heritage-entities',
      component: HeritageEntities,
      meta: {
        title: 'Heritage Entities',
        description: 'Search and explore heritage entities.',
      }
    },
    {
      path: '/heritage-entities/:id',
      props: true,
      component: HeritageEntityDetail,
      meta: {
        title: 'Heritage Entity',
        description: 'Heritage entity detail',
      }
    },
    {
      path: '/resource/:id',
      props: true,
      component: Resource,
    },
    {
      path: '/subject/:id',
      props: true,
      component: Subject,
    },
    {
      path: '/publisher',
      props: true,
      component: Publisher,
    },
    {
      path: '/page/:id',
      redirect: '/resource/:id',
    },
    {
      path: '/page/:id/json',
      redirect: '/resource/:id/json',
    },
    {
      path: '/about',
      component: About,
      meta: {
        title: 'About',
        description: 'About page'
      }
    },
    {
      path: '/artemisia',
      component: ArtemisIA,
      meta: {
        title: 'Artemisia',
        description: 'AI search assistant',
      }
    },
    {
      path: '/guide',
      component: Guide,
      meta: {
        title: 'Guide',
        description: 'Guide over the ARIADNE portal'
      }
    },
    {
      path: '/services',
      component: Services,
      meta: {
        title: 'Services',
        description: 'Services page'
      }
    },
    {
      path: '/services/:id',
      props: true,
      component: ServiceDetail,
      meta: {
        title: 'Service',
        description: 'Service detail',
      }
    },
    {
      path: '/infographic',
      component: Infographic,
      meta: {
        title: 'Infographic',
        description: 'Infographic',
      }
    },
    {
      path: '/theme',
      component: Theme,
      meta: {
        title: 'Theme',
        description: 'Site theme',
      }
    },
    {
      path: '/:pathMatch(.*)*',
      component: NotFound,
      meta: {
        title: '404',
        description: '404 the page was not found',
      }
    },
  ],

  scrollBehavior(to: any, from: any, pos: any) {
    if (from.path !== to.path) {
      return { top: 0 };
    }
    return pos;
  },
});

router.beforeEach((to: any, from: any, next: any) => {
  if (to?.meta?.title) {
    generalModule.setMeta({
      title: to.meta.title,
      description: to.meta.description,
    });
  }

  // maybe update resource back link
  resourceModule.maybeUpdateFromPath({
    from: from?.path,
    to: to?.path,
  });

  // Let breadCrumbModule module know from where user is landing on page
  if (from?.path) {
    breadCrumbModule.setFrom(from.path);
  }

  next();
});

export default router;
