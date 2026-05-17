import { library } from '@fortawesome/fontawesome-svg-core'
import {
  faBan,
  faEye,
  faEyeSlash,
  faMagnifyingGlass,
  faSpinner,
} from '@fortawesome/free-solid-svg-icons'

export function registerFontAwesome() {
  library.add(
    faBan,
    faEye,
    faEyeSlash,
    faMagnifyingGlass,
    faSpinner,
  )
}
