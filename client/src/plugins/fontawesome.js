import { library } from '@fortawesome/fontawesome-svg-core'
import {
  faBan,
  faEye,
  faEyeSlash,
  faMagnifyingGlass,
  faPenToSquare,
  faPlus,
  faSave,
  faSpinner,
} from '@fortawesome/free-solid-svg-icons'

export function registerFontAwesome() {
  library.add(
    faBan,
    faEye,
    faEyeSlash,
    faMagnifyingGlass,
    faPenToSquare,
    faPlus,
    faSpinner,
    faSave
  )
}
