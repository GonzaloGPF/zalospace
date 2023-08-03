import Formatter from '@/objects/Formatter.js'
import Translator from '@/objects/Translator.js'
import { computed } from 'vue'

export default computed(() => [
  {
    title: Translator.t('jobs.web_developer'),
    url: 'https://salseroapp.com/index',
    subtitle: Formatter.formatDiff('2015-03-01', '2015-09-01'),
    urlLabel: 'SalseroApp',
    image: 'images/companies/salseroapp.png',
    paragraphs: [
      Translator.t('jobs.salsero_app_1'),
      Translator.t('jobs.salsero_app_2'),
      Translator.t('jobs.salsero_app_3')
    ]
  },
  {
    title: Translator.t('jobs.qa_tester_engineer'),
    url: 'https://www.soprasteria.com/',
    subtitle: Formatter.formatDiff('2015-11-01', '2016-03-01'),
    urlLabel: 'Sopra Steria',
    image: 'images/companies/sopra.png',
    paragraphs: [
      Translator.t('jobs.sopra_steria_1'),
      Translator.t('jobs.sopra_steria_2'),
      Translator.t('jobs.sopra_steria_3')
    ]
  },
  {
    title: Translator.t('jobs.fullstack_developer'),
    url: 'https://www.indracompany.com/es/',
    subtitle: Formatter.formatDiff('2016-03-01', '2017-03-01'),
    urlLabel: 'Indra',
    image: 'images/companies/indra.png',
    paragraphs: [
      Translator.t('jobs.indra_1'),
      Translator.t('jobs.indra_2'),
      Translator.t('jobs.indra_3'),
      Translator.t('jobs.indra_4'),
      Translator.t('jobs.indra_5')
    ]
  },
  {
    title: Translator.t('jobs.web_developer'),
    url: 'https://boream.com/',
    subtitle: Formatter.formatDiff('2017-03-01', '2017-06-01'),
    urlLabel: 'Boream',
    image: 'images/companies/boream.png',
    paragraphs: [
      Translator.t('jobs.boream_1'),
      Translator.t('jobs.boream_2')
    ]
  },
  {
    title: Translator.t('jobs.fullstack_developer'),
    url: 'https://eugen.solutions/',
    subtitle: Formatter.formatDiff('2017-06-01', '2017-10-01'),
    urlLabel: 'Eugen',
    image: 'images/companies/eugen.png',
    paragraphs: [
      Translator.t('jobs.eugen_1'),
      Translator.t('jobs.eugen_2'),
      Translator.t('jobs.eugen_3')
    ]
  },
  {
    title: Translator.t('jobs.fullstack_developer'),
    url: 'https://grupomobius.com//',
    subtitle: Formatter.formatDiff('2017-11-01', '2022-02-01'),
    urlLabel: 'Grupo Mobius',
    image: 'images/companies/mobius.png',
    paragraphs: [
      Translator.t('jobs.mobius_1'),
      Translator.t('jobs.mobius_2'),
      Translator.t('jobs.mobius_3'),
      Translator.t('jobs.mobius_4'),
      Translator.t('jobs.mobius_5'),
      Translator.t('jobs.mobius_6')
    ]
  },
  {
    title: Translator.t('jobs.senior_frontend'),
    url: 'https://acierto.com//',
    subtitle: Formatter.formatDiff('2022-02-01', new Date()),
    urlLabel: 'Acierto',
    image: 'images/companies/acierto.png',
    paragraphs: [
      Translator.t('jobs.mobius_1'),
      Translator.t('jobs.mobius_2'),
      Translator.t('jobs.mobius_3'),
      Translator.t('jobs.mobius_4'),
      Translator.t('jobs.mobius_5'),
      Translator.t('jobs.mobius_6')
    ]
  }
])
