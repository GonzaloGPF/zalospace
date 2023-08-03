import Formatter from '@/objects/Formatter.js'
import Translator from '@/objects/Translator.js'
import { computed } from 'vue'
import technologies, { TechNames } from '@/data/technologies.js'

function only (names) {
  return technologies.filter(tech => names.includes(tech.name))
}

export default computed(() => [
  {
    title: 'ZaloSpace v2',
    subtitle: Formatter.formatDiff('2023-06-01', new Date()),
    image: 'images/projects/zalospace_v2.png',
    url: 'https://zalospace.com/',
    paragraphs: [
      Translator.t('projects.zalospace_v2_1'),
      Translator.t('projects.zalospace_v2_2'),
      Translator.t('projects.zalospace_v2_3')
    ],
    technologies: only([TechNames.PHP, TechNames.VUE, TechNames.TAILWINDCSS, TechNames.AWS, TechNames.ANALYTICS, TechNames.FORGE, TechNames.INERTIAJS, TechNames.PHPUNIT, TechNames.GSAP, TechNames.VITE, TechNames.GIT])
  },
  {
    title: 'Talent Battles',
    subtitle: Formatter.formatDiff('2019-08-13', new Date()),
    image: 'images/projects/talentbattles.png',
    url: 'https://talentbattles.com/',
    paragraphs: [
      Translator.t('projects.talentbattles_1'),
      Translator.t('projects.talentbattles_2'),
      Translator.t('projects.talentbattles_3'),
      Translator.t('projects.talentbattles_4'),
      Translator.t('projects.talentbattles_5'),
      Translator.t('projects.talentbattles_6'),
      Translator.t('projects.talentbattles_7')
    ],
    technologies: only([TechNames.PHP, TechNames.VUE, TechNames.TAILWINDCSS, TechNames.AWS, TechNames.ANALYTICS, TechNames.FORGE, TechNames.HORIZON, TechNames.PHPUNIT, TechNames.GSAP, TechNames.AMPLITUDEJS, TechNames.D3, TechNames.LARECIPE, TechNames.SASS, TechNames.STRIPE, TechNames.MYSQL, TechNames.VITE, TechNames.VUETIFY, TechNames.GIT, TechNames.WEBSOCKETS, TechNames.PUSHER])
  },
  {
    title: 'Pegasus',
    subtitle: Formatter.formatDiff('2017-09-01', '2022-02-01'),
    image: 'images/projects/pegasus.png',
    url: 'https://pegasus.grupomobius.com/',
    paragraphs: [
      Translator.t('projects.pegasus_1'),
      Translator.t('projects.pegasus_2'),
      Translator.t('projects.pegasus_3'),
      Translator.t('projects.pegasus_4'),
      Translator.t('projects.pegasus_5')
    ],
    technologies: only([TechNames.PHP, TechNames.VUE, TechNames.TAILWINDCSS, TechNames.AWS, TechNames.HIGHCHARTS, TechNames.FORGE, TechNames.HORIZON, TechNames.PHPUNIT, TechNames.SCRIBE, TechNames.SASS, TechNames.MYSQL, TechNames.VITE, TechNames.VUETIFY, TechNames.GIT, TechNames.WEBSOCKETS, TechNames.PUSHER])
  },
  {
    title: 'Asamblea Real Madrid',
    subtitle: Formatter.formatDiff('2016-09-01', '2016-10-30'),
    image: 'images/projects/real_madrid.png',
    paragraphs: [
      Translator.t('projects.real_madrid_1'),
      Translator.t('projects.real_madrid_2'),
      Translator.t('projects.real_madrid_3'),
      Translator.t('projects.real_madrid_4')
    ],
    technologies: only([TechNames.PHP, TechNames.VUE, TechNames.MYSQL])
  },
  {
    title: 'System Engineering Tier 1',
    subtitle: Formatter.formatDiff('2016-06-11', '2016-08-01'),
    image: 'images/projects/set1.png',
    paragraphs: [
      Translator.t('projects.set1_1'),
      Translator.t('projects.set1_2'),
      Translator.t('projects.set1_3'),
      Translator.t('projects.set1_4')
    ],
    technologies: only([TechNames.PHP, TechNames.VUE, TechNames.HIGHCHARTS, TechNames.MYSQL])
  },
  {
    title: 'ZaloSpace',
    subtitle: Formatter.formatDiff('2016-01-12', '2016-11-21'),
    image: 'images/projects/zalospace_v1.png',
    paragraphs: [
      Translator.t('projects.zalospace_1'),
      Translator.t('projects.zalospace_2'),
      Translator.t('projects.zalospace_3'),
      Translator.t('projects.zalospace_4')
    ],
    technologies: only([TechNames.PHP, TechNames.VUE, TechNames.GSAP, TechNames.MYSQL, TechNames.ANALYTICS, TechNames.BOOTSTRAP])
  },
  {
    title: 'Control Bluetooth Programable',
    subtitle: Formatter.formatDiff('2015-08-01', '2015-10-30'),
    image: 'images/projects/bt_android.png',
    paragraphs: [
      Translator.t('projects.bt_android_1'),
      Translator.t('projects.bt_android_2'),
      Translator.t('projects.bt_android_3'),
      Translator.t('projects.bt_android_4'),
      Translator.t('projects.bt_android_5')
    ],
    technologies: only([TechNames.ANDROID])
  },
  {
    title: 'Vehículo Arduino',
    subtitle: Formatter.formatDiff('2015-07-01', '2015-09-01'),
    image: 'images/projects/arduino.jpeg',
    paragraphs: [
      Translator.t('projects.arduino_1'),
      Translator.t('projects.arduino_2'),
      Translator.t('projects.arduino_3'),
      Translator.t('projects.arduino_4'),
      Translator.t('projects.arduino_5'),
      Translator.t('projects.arduino_6'),
      Translator.t('projects.arduino_7'),
      Translator.t('projects.arduino_8'),
      Translator.t('projects.arduino_9'),
      Translator.t('projects.arduino_10'),
      Translator.t('projects.arduino_11')
    ],
    technologies: only([TechNames.ARDUINO])
  },
  {
    title: 'Cannon Defense',
    subtitle: Formatter.formatDiff('2015-06-01', '2015-07-01'),
    image: 'images/projects/cannon_defense.png',
    paragraphs: [
      Translator.t('projects.cannon_defense_1'),
      Translator.t('projects.cannon_defense_2')
    ],
    technologies: only([TechNames.CREATEJS])
  }
])
