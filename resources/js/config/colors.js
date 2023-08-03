const colors = {
  bgColors: {
    success: 'bg-green-200 dark:bg-green-600',
    info: 'bg-blue-300 dark:bg-blue-700',
    warning: 'bg-yellow-200 dark:bg-yellow-500',
    danger: 'bg-red-200 dark:bg-red-500',
    default: 'bg-gray-300 dark:bg-gray-500'
  },
  bgHoverColors: {
    success: 'hover:bg-green-100 dark:hover:bg-green-500',
    info: 'hover:bg-blue-200 dark:hover:bg-blue-600',
    warning: 'hover:bg-yellow-100 dark:hover:bg-yellow-400',
    danger: 'hover:bg-red-100 dark:hover:bg-red-400',
    default: 'hover:bg-gray-50 dark:hover:bg-gray-700'
  },
  textColors: {
    success: 'text-green-700 dark:text-green-100',
    info: 'text-blue-700 dark:text-blue-300',
    warning: 'text-yellow-700 dark:text-white',
    danger: 'text-red-700 dark:text-red-100',
    default: 'text-white'
  },
  borerColors: {
    success: 'border-green-700 dark:border-green-300',
    info: 'border-blue-700 dark:border-blue-300',
    warning: 'border-yellow-700 dark:border-yellow-100',
    danger: 'border-red-700 dark:border-red-300',
    default: 'border-gray-300 dark:border-gray-500'
  },
  getBgColor (type, defaultColor) {
    return colors.bgColors[type] || defaultColor || colors.bgColors.default
  },
  getBgHoverColor (type, defaultColor) {
    return colors.bgHoverColors[type] || defaultColor || colors.bgHoverColors.default
  },
  getTextColor (type, defaultColor) {
    return colors.textColors[type] || defaultColor || colors.textColors.default
  },
  getBorderColor (type, defaultColor) {
    return colors.borerColors[type] || defaultColor || colors.borerColors.default
  }
}

export default colors
