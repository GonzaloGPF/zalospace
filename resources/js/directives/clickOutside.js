export default {
  beforeMount(element) {
    element.clickOutsideEvent = (event) => {
      //  check that click was outside the el and his children
      if (!(element === event.target || element.contains(event.target))) {
        // and if it did, call method provided in attribute value
        // vnode.context[binding.expression](event);
        // binding.value(); run the arg
        element.dispatchEvent(new Event('click-outside'))
      }
    }
    document.body.addEventListener('click', element.clickOutsideEvent)
  },
  unmounted(element) {
    document.body.removeEventListener('click', element.clickOutsideEvent)
  },
}
