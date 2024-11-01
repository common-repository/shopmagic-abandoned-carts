import 'dialog-polyfill/dist/dialog-polyfill.css'
import dialogPolyfill from 'dialog-polyfill';
import elementReady from 'element-ready';
import Cookies from "js-cookie";

const DIALOG_CLOSE_VALUE = 'close';
const SM_POPUP_COOKIE = 'shopmagic_exit_popup';

const testMode = () => {
	return Cookies.get(SM_POPUP_COOKIE) === 'test';
};

const setCookie = (): void => {
	if (testMode()) return
	Cookies.set(SM_POPUP_COOKIE, '1', {expires: 1 / 24})
}

(async () => {
	if (Cookies.get(SM_POPUP_COOKIE) && ! testMode()) return

	const dialog = await elementReady('dialog.shopmagic-exit-intent', {timeout: 4000});
	if (!dialog) return
	dialogPolyfill.registerDialog(dialog)

	const showModalEvent = ({clientX, clientY}: MouseEvent): void => {
		if (
			! dialog.open &&
			( clientY <= 0 ||
				clientX <= 0 ||
				(clientX >= window.innerWidth || clientY >= window.innerHeight) )
		) {
			dialog.showModal();
		}
	}

	dialog.querySelector<HTMLButtonElement>('button.shopmagic-exit-intent__close')!
		.addEventListener('click', (): void => {
			dialog.close(DIALOG_CLOSE_VALUE)
		})

	dialog.addEventListener('close', (): void => {
		setCookie();

		if (!testMode()) {
			document.body.removeEventListener('mouseleave', showModalEvent)
		}

		if (dialog.returnValue === DIALOG_CLOSE_VALUE) return

		const form = dialog.querySelector<HTMLFormElement>('form.shopmagic-exit-intent__form')

		fetch(SMAbandonedCarts.ajax_url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: new URLSearchParams(new FormData(form))
		})
	}, {once: true})

	document.body.addEventListener("mouseleave", showModalEvent)
})()
