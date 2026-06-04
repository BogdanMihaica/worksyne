import { library } from '@fortawesome/fontawesome-svg-core'
import {
  faBan,
  faCheck,
  faEye,
  faEyeSlash,
  faMagnifyingGlass,
  faPaperPlane,
  faPenToSquare,
  faPlus,
  faSave,
  faSpinner,
  faX,
} from '@fortawesome/free-solid-svg-icons'

export function registerFontAwesome() {
  library.add(
    faBan,
    faEye,
    faEyeSlash,
    faMagnifyingGlass,
    faPaperPlane,
    faPenToSquare,
    faPlus,
    faSpinner,
    faSave,
    faX,
    faCheck
  )
}
