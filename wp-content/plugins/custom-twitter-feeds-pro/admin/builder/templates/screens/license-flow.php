<div class="ctf-fb-full-wrapper ctf-fb-fs ctf-license-flow-wrapper" v-if="!activationPageDismissed">
    <section class="sb-dash-license-ctn">
        <article class="sb-dash-license-section">
            <div class="sb-dash-license-centered">
                <span class="sb-dash-license-logo" v-html="svgIcons.xAndSmushLogo"></span>
            </div>
            <div class="sb-dash-license-centered">
                <strong class="sb-text-tiny"><?php _e( 'Custom Twitter Feeds Pro by Smash Balloon', 'custom-twitter-feeds' ) ?></strong>
                <h3 class="sb-h3">Activate your plugin to get started</h3>
            </div>
            <div class="sb-dash-license-centered">
                <div class="sb-dash-license-input-ctn">
                    <div class="sb-input-ctn sb-input  sb-input-medium">
                        <div class="sb-input-insider">
                            <input type="password" placeholder="Paste license key here" v-model="licenseKey">
                        </div>
                    </div>
                    <button class="sb-btn  sb-btn-blue" data-icon-position="left" data-onlyicon="true" @click.prevent.default="licenseFlowActiveLicense()">
                        <span class="sb-btn-icon">
                            <svg width="13" height="10" viewBox="0 0 13 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M4.46447 6.88917L10.8284 0.525204L12.2426 1.93942L4.46447 9.71759L0.221826 5.47495L1.63604 4.06074L4.46447 6.88917Z"></path>
                            </svg>
                        </span>
                        Activate
                    </button>
                </div>
            </div>
            <div class="sb-dash-license-centered">
                <div class="sb-dash-license-buy-ctn">
                    <span class="sb-dark-text sb-text-small">Don’t have a license key?</span>
                    <a class="sb-bold sb-text-small sb-link sb-flex " href="https://smashballoon.com/pricing/twitter-feed/?twitter&amp;utm_campaign=twitter-pro&amp;utm_source=welcome&amp;utm_medium=license&amp;utm_content=Buy" target="_blank" rel="noreferrer">
                        Buy a License
                        <span class="sb-dash-license-buy-icon sb-custom-icon-size" style="width: 8px;">
                            <svg width="6" height="8" viewBox="0 0 6 8" fill="#141B38" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1.66681 0L0.726807 0.94L3.78014 4L0.726807 7.06L1.66681 8L5.66681 4L1.66681 0Z"></path>
                            </svg>
                        </span>
                    </a>
                </div>
            </div>
        </article>
        <article class="sb-dash-license-section sb-dash-license-centered-cards">
            <div class="sb-dash-license-centered">
                <h4 class="sb-h4">After activating your plugin, you can...</h4>
            </div>
            <div class="sb-dash-license-info-cards">
                <div class="sb-dash-license-card">
                    <span class="">
                        <svg width="20" height="21" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4.12499 10.15L1.79999 9.15003C1.46665 9.01669 1.27099 8.77503 1.21299 8.42503C1.15432 8.07503 1.24999 7.77503 1.49999 7.52503L4.64999 4.37503C4.88332 4.14169 5.15832 3.97503 5.47499 3.87503C5.79165 3.77503 6.11665 3.75836 6.44999 3.82503L7.74999 4.10003C6.86665 5.15003 6.16265 6.10436 5.63799 6.96303C5.11265 7.82103 4.60832 8.88336 4.12499 10.15ZM18.825 0.400026C18.9583 0.400026 19.0917 0.433359 19.225 0.500026C19.3583 0.566693 19.475 0.650026 19.575 0.750026C19.675 0.850026 19.7583 0.966693 19.825 1.10003C19.8917 1.23336 19.925 1.36669 19.925 1.50003C19.9917 3.05003 19.6627 4.65836 18.938 6.32503C18.2127 7.99169 17.1917 9.48336 15.875 10.8C14.975 11.7 14.1083 12.4207 13.275 12.962C12.4417 13.504 11.4583 14.0167 10.325 14.5C10.1083 14.5834 9.88765 14.625 9.66299 14.625C9.43765 14.625 9.24165 14.5417 9.07499 14.375L5.94999 11.25C5.78332 11.0834 5.69999 10.8874 5.69999 10.662C5.69999 10.4374 5.74165 10.2167 5.82499 10C6.30832 8.88336 6.82099 7.90403 7.36299 7.06203C7.90432 6.22069 8.62499 5.35003 9.52499 4.45003C10.8417 3.13336 12.3333 2.11236 14 1.38703C15.6667 0.662359 17.275 0.333359 18.825 0.400026ZM12.475 7.85003C12.8583 8.23336 13.3293 8.42503 13.888 8.42503C14.446 8.42503 14.9167 8.23336 15.3 7.85003C15.6833 7.46669 15.875 6.99569 15.875 6.43703C15.875 5.87903 15.6833 5.40836 15.3 5.02503C14.9167 4.64169 14.446 4.45003 13.888 4.45003C13.3293 4.45003 12.8583 4.64169 12.475 5.02503C12.0917 5.40836 11.9 5.87903 11.9 6.43703C11.9 6.99569 12.0917 7.46669 12.475 7.85003ZM10.175 16.2C11.4417 15.7167 12.5083 15.2127 13.375 14.688C14.2417 14.1627 15.2 13.4584 16.25 12.575L16.5 13.875C16.5667 14.2084 16.55 14.5377 16.45 14.863C16.35 15.1877 16.1833 15.4667 15.95 15.7L12.8 18.85C12.55 19.1 12.25 19.1917 11.9 19.125C11.55 19.0584 11.3083 18.8584 11.175 18.525L10.175 16.2ZM2.04999 14.05C2.63332 13.4667 3.34165 13.1707 4.17499 13.162C5.00832 13.154 5.71665 13.4417 6.29999 14.025C6.88332 14.6084 7.17499 15.3167 7.17499 16.15C7.17499 16.9834 6.88332 17.6917 6.29999 18.275C5.88332 18.6917 5.18765 19.05 4.21299 19.35C3.23765 19.65 1.89165 19.9167 0.174988 20.15C0.408321 18.4334 0.674988 17.0917 0.974988 16.125C1.27499 15.1584 1.63332 14.4667 2.04999 14.05Z" fill="#0068A0"></path>
                        </svg>
                    </span>
                    <strong class="sb-text-small sb-dark-text">Collect New Posts/Tweets</strong>
                    <p class="sb-text-tiny sb-dark2-text">New posts/tweets and data related to the posts for your business are collected from external sites. Your license key can be used to access this data using Smash Balloon's API site.</p>
                </div>
                <div class="sb-dash-license-card">
                    <span class="">
                        <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 18V16L5 15H2C1.45 15 0.979333 14.8043 0.588 14.413C0.196 14.021 0 13.55 0 13V2C0 1.45 0.196 0.979 0.588 0.587C0.979333 0.195667 1.45 0 2 0H10V2H2V13H18V10H20V13C20 13.55 19.8043 14.021 19.413 14.413C19.021 14.8043 18.55 15 18 15H15L16 16V18H4ZM13 12L8 7L9.4 5.6L12 8.175V0H14V8.175L16.6 5.6L18 7L13 12Z" fill="#0068A0"></path>
                        </svg>
                    </span>
                    <strong class="sb-text-small sb-dark-text">Recieve Critical Updates</strong>
                    <p class="sb-text-tiny sb-dark2-text">With constant changes to WordPress and the APIs that your reviews are retrieved from, your plugin needs to stay up to date. Activating your license allows automatic updates from the Plugins page.</p>
                </div>
                <div class="sb-dash-license-card">
                    <span class="">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.45 7.1C17.05 6.05 16.4543 5.129 15.663 4.337C14.871 3.54567 13.95 2.95 12.9 2.55L11.75 5.35C12.4333 5.6 13.025 5.979 13.525 6.487C14.025 6.99567 14.4167 7.58333 14.7 8.25L17.45 7.1ZM7.15 2.55C6.08333 2.95 5.15 3.55 4.35 4.35C3.55 5.15 2.95 6.08333 2.55 7.15L5.3 8.3C5.58333 7.6 5.97933 6.98767 6.488 6.463C6.996 5.93767 7.6 5.55 8.3 5.3L7.15 2.55ZM2.55 12.85C2.93333 13.9167 3.525 14.85 4.325 15.65C5.125 16.45 6.05 17.05 7.1 17.45L8.3 14.7C7.6 14.45 6.996 14.0623 6.488 13.537C5.97933 13.0123 5.58333 12.4 5.3 11.7L2.55 12.85ZM12.9 17.45C13.95 17.05 14.871 16.4543 15.663 15.663C16.4543 14.871 17.05 13.95 17.45 12.9L14.7 11.7C14.45 12.4 14.0667 13.004 13.55 13.512C13.0333 14.0207 12.4333 14.4167 11.75 14.7L12.9 17.45ZM10 20C8.61667 20 7.31667 19.7373 6.1 19.212C4.88333 18.6873 3.825 17.975 2.925 17.075C2.025 16.175 1.31267 15.1167 0.788 13.9C0.262667 12.6833 0 11.3833 0 10C0 8.61667 0.262667 7.31667 0.788 6.1C1.31267 4.88333 2.025 3.825 2.925 2.925C3.825 2.025 4.88333 1.31233 6.1 0.787C7.31667 0.262333 8.61667 0 10 0C11.3833 0 12.6833 0.262333 13.9 0.787C15.1167 1.31233 16.175 2.025 17.075 2.925C17.975 3.825 18.6873 4.88333 19.212 6.1C19.7373 7.31667 20 8.61667 20 10C20 11.3833 19.7373 12.6833 19.212 13.9C18.6873 15.1167 17.975 16.175 17.075 17.075C16.175 17.975 15.1167 18.6873 13.9 19.212C12.6833 19.7373 11.3833 20 10 20ZM10 13C10.8333 13 11.5417 12.7083 12.125 12.125C12.7083 11.5417 13 10.8333 13 10C13 9.16667 12.7083 8.45833 12.125 7.875C11.5417 7.29167 10.8333 7 10 7C9.16667 7 8.45833 7.29167 7.875 7.875C7.29167 8.45833 7 9.16667 7 10C7 10.8333 7.29167 11.5417 7.875 12.125C8.45833 12.7083 9.16667 13 10 13Z" fill="#0068A0"></path>
                        </svg>
                    </span>
                    <strong class="sb-text-small sb-dark-text">Receive Technical Support</strong>
                    <p class="sb-text-tiny sb-dark2-text">Although we pride ourselves on creating reliable, easy-to-use products our expert support team is here to help with any questions. Activating your license key is recommended to receive efficient support.</p>
                </div>
            </div>
        </article>
        <article class="sb-dash-license-section sb-dash-support-form">
            <div class="sb-dash-license-centered"><img src="<?php echo CTF_BUILDER_URL . 'assets/img/support-team.jpg'; ?>" alt="Amazing Support Team."></div>
            <div class="sb-dash-license-centered sb-dash-license-support-heading">
                <h3 class="sb-h3 sb-light-text">Having trouble using the plugin?</h3>
                <h3 class="sb-h3 sb-dark-text">Contact our friendly support team.</h3>
            </div>
            <div class="sb-dash-license-centered">
                <a href="https://smashballoon.com/support/?twitter&utm_campaign=twitter-pro&utm_source=welcome&utm_medium=support&utm_content=Go" target="_blank" class="sb-btn sb-btn-blue sb-btn-medium" data-icon-position="right" data-onlyicon="true">
                    Go to Support Form
                    <span class="sb-btn-icon sb-custom-icon-size">
                        <svg width="6" height="8" viewBox="0 0 6 8" fill="#141B38" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1.66681 0L0.726807 0.94L3.78014 4L0.726807 7.06L1.66681 8L5.66681 4L1.66681 0Z"></path>
                        </svg>
                    </span>
                </a>
            </div>
        </article>
    </section>
</div>