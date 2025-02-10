<!-- index.html -->
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/vue@2.7.16"></script>

  <title>WHMCS Admin MiniApp</title>
</head>

<body>
  <main id="app" class="container pt-2">
    <!-- Placeholder for user info -->
    <div class="card mb-3">
      <div class="card-header">
        <div v-text="fullname"></div>
        <div class="row">
          <div class="col">
            <code v-text="username"></code>
          </div>
          <div class="col-auto">
            <small><code v-text="userid"></code></small>
          </div>
        </div>
      </div>
    </div>

    <!-- Response from PHP -->
    <div
      v-for="(alert,index) in alerts"
      :key="index"
      :class="'alert alert-' + alert.type"
      v-text="alert.text"></div>

    <!-- Input Form -->
    <form v-if="currencies.length" @submit.prevent="submitExchangeRate" :disabled="sending">
      <div v-for="(item,index) in currencies" :key="index" class="mb-3">
        <label class="form-label">Exchange rate for <code v-text="item.code"></code></label>
        <input
          v-model="item.rate"
          type="number"
          class="form-control"
          :disabled="item.isBase || sending">
      </div>

      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary mb-3" :disabled="sending">Submit <span v-if="sending">...</span></button>
      </div>
    </form>

    <div v-if="loadingCurrencies" class="text-center py-4">Loading...</div>

  </main>

  <script>
    var app = new Vue({
      el: '#app',
      data: {
        currencies: [],
        initData: {},
        user: {},
        alerts: [],
        loadingCurrencies: false,
        sending: false,
      },
      mounted() {
        const tgHash = window.location.hash.slice(1);
        const tgHashParams = new URLSearchParams(tgHash);
        this.initData = tgHashParams.get('tgWebAppData');
        if (this.initData) {
          const initDataParams = new URLSearchParams(this.initData);
          this.user = JSON.parse(initDataParams.get('user'));
          this.loadCurrencies();
        } else {
          alert('No initData found!');
        }
      },
      computed: {
        fullname() {
          const r = [this.user.first_name];
          if (this.user.last_name) {
            r.push(this.user.last_name);
          }
          return r.join(' ');;
        },
        username() {
          return this.user.username;
        },
        userid() {
          return this.user.id;
        }
      },
      methods: {
        async api(formData) {
          try {
            const res = await fetch('', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                initData: this.initData,
                formData: formData,
              })
            });
            return await res.json();
          } catch (err) {
            console.error(err);
            return {};
          }
        },
        async loadCurrencies() {
          if (this.loadingCurrencies) return;
          this.loadingCurrencies = true;
          const resp = await this.api({
            action: 'getCurrencies'
          });
          this.currencies = resp.map(o => {
            o.rate = parseFloat(o.rate);
            return o;
          });
          this.loadingCurrencies = false;
        },
        addAlert(resp) {
          const alert = {};
          if (resp.message) {
            alert.type = 'info';
            alert.text = resp.message;
          } else if (resp.error) {
            alert.type = 'error';
            alert.text = resp.error;
          }
          this.alerts.push(alert);
        },
        async submitExchangeRate() {
          if (this.sending) return;
          this.sending = true;
          for (const item of this.currencies) {
            if (item.isBase) continue;
            const res = await this.api({
              action: 'updateCurrency',
              currencyCode: item.code,
              exchangeRate: item.rate,
            })
            this.addAlert(res);
          }
          this.sending = false;
        }
      }
    })
  </script>
</body>

</html>