/**
 * Medicine-Shield (Oushodh-Shield)
 * Citizen Gamification & Reward Engine
 */

class RewardManager {
  constructor() {
    if (window.CURRENT_USER && window.CURRENT_USER.id) {
      this.userId = String(window.CURRENT_USER.id);
    } else {
      this.userId = localStorage.getItem('ms_user_id') || 'citizen_' + Math.floor(1000 + Math.random() * 9000);
      localStorage.setItem('ms_user_id', this.userId);
    }
    this.points = 150;
    this.badge = 'Health Guardian Lv. 1';
    this.fetchProfile();
  }

  async fetchProfile() {
    try {
      const res = await fetch('api/rewards.php?user_id=' + encodeURIComponent(this.userId));
      const data = await res.json();
      if (data.success && data.profile) {
        this.points = data.profile.points;
        this.badge = data.profile.badge_title;
        this.updateNavBadge();
      }
    } catch (e) {
      console.warn('Error fetching reward profile:', e);
    }
  }

  updateNavBadge() {
    const badgeEl = document.getElementById('user-points-badge');
    if (badgeEl) {
      badgeEl.innerHTML = '⭐ <strong>' + this.points + ' pts</strong> • ' + this.badge;
    }
  }

  async redeemReward(rewardType) {
    if (!confirm('Are you sure you want to redeem "' + rewardType + '"?')) return;

    try {
      const res = await fetch('api/rewards.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          user_id: this.userId,
          reward_type: rewardType
        })
      });

      const data = await res.json();
      if (data.success) {
        if (window.app) window.app.playSound('success');
        alert('🎉 ' + data.message + '\n\nYour Claim Voucher Code: ' + data.voucher_code + '\n\nShow this code at any registered pharmacy or enter it in your mobile operator app.');
        this.fetchProfile();
      } else {
        alert('⚠️ ' + (data.error || 'Failed to redeem reward.'));
      }
    } catch (err) {
      console.error(err);
      alert('Error connecting to reward server.');
    }
  }
}

window.rewardManager = new RewardManager();
