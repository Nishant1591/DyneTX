<div class="ctf-fb-connectaccount-pp-ctn sb-fs-boss ctf-fb-center-boss" v-if="viewsActive.connectAccountPopup">
	<div class="ctf-fb-connectaccount-popup ctf-fb-popup-inside" data-step="2" v-if="viewsActive.connectAccountStep == 'step_2'">
		<div class="ctf-fb-popup-cls" @click.prevent.default="closeConnectAccountPopup()">
			<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M14 1.41L12.59 0L7 5.59L1.41 0L0 1.41L5.59 7L0 12.59L1.41 14L7 8.41L12.59 14L14 12.59L8.41 7L14 1.41Z" fill="#141B38"/>
			</svg>
		</div>
		<div class="ctf-fb-types ctf-fb-fs">
			<h3>{{connectAccountScreen.heading_2}}</h3>
			<div class="ctf-fb-source-inputs ctf-fb-fs">
				<div class="ctf-fb-fs">
					<div class="ctf-fb-source-inp-label ctf-fb-fs"><span class="sb-caption sb-caption-lighter">{{connectAccountScreen.manualModal.name}}</span></div>
					<input type="text" class="ctf-fb-source-inp ctf-fb-fs" v-model="appCredentials.app_name" :placeholder="connectAccountScreen.manualModal.namePlhdr">
				</div>

				<div class="ctf-fb-source-inp-half ctf-fb-fs">
					<div class="ctf-fb-fs">
						<div class="ctf-fb-source-inp-label ctf-fb-fs"><span class="sb-caption sb-caption-lighter">{{connectAccountScreen.manualModal.consumerKey}}</span></div>
						<input type="text" class="ctf-fb-source-inp ctf-fb-fs" v-model="appCredentials.consumer_key" :placeholder="connectAccountScreen.manualModal.consumerKeyPlhdr">
					</div>
					<div class="ctf-fb-fs">
						<div class="ctf-fb-source-inp-label ctf-fb-fs"><span class="sb-caption sb-caption-lighter">{{connectAccountScreen.manualModal.consumerSecret}}</span></div>
						<input type="text" class="ctf-fb-source-inp ctf-fb-fs" v-model="appCredentials.consumer_secret" :placeholder="connectAccountScreen.manualModal.consumerSecretPlhdr">
					</div>
				</div>
				<div class="ctf-fb-source-inp-half ctf-fb-fs">
					<div class="ctf-fb-fs">
						<div class="ctf-fb-source-inp-label ctf-fb-fs"><span class="sb-caption sb-caption-lighter">{{connectAccountScreen.manualModal.accessToken}}</span></div>
						<input type="text" class="ctf-fb-source-inp ctf-fb-fs" v-model="appCredentials.access_token" :placeholder="connectAccountScreen.manualModal.accessTokenPlhdr">
					</div>
					<div class="ctf-fb-fs">
						<div class="ctf-fb-source-inp-label ctf-fb-fs"><span class="sb-caption sb-caption-lighter">{{connectAccountScreen.manualModal.accessTokenSecret}}</span></div>
						<input type="text" class="ctf-fb-source-inp ctf-fb-fs" v-model="appCredentials.access_token_secret" :placeholder="connectAccountScreen.manualModal.accessTokenSecretPlhdr">
					</div>
				</div>
				<strong v-show="manualAccountResp != false" class="ctf-fb-source-msg" :class="manualAccountResp === 'success' ? 'ctf-fb-source-msg-success' : 'ctf-fb-source-msg-error'" v-html="manualAccountResp === 'success' ? genericText.successManualAccount : genericText.errorManualAccount"></strong>
				<button class="ctf-fb-source-btn ctf-fb-fs sb-btn-blue sb-account-connection-button" :data-active="checkManualEmpty() && loadingAjax == false ? 'true' : 'false'" @click.prevent.default="connectManualAccount()">
					<div v-if="loadingAjax === false && manualAccountResp === 'success'" class="ctf-fb-icon-success"></div>
					<span v-if="loadingAjax === false">{{genericText.add}}</span>
					<span v-if="loadingAjax" class="spinner" style="display: inline-block;visibility: visible;margin: 1px;"></span>
				</button>

			</div>

		</div>
	</div>


</div>