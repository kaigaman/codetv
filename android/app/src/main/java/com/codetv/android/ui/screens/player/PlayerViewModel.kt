package com.codetv.android.ui.screens.player

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.codetv.android.data.api.CodetvRepository
import com.codetv.android.data.model.Channel
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

data class PlayerUiState(
    val channel: Channel? = null,
    val relatedChannels: List<Channel> = emptyList(),
    val isLoading: Boolean = true,
    val error: String? = null,
)

@HiltViewModel
class PlayerViewModel @Inject constructor(
    private val repository: CodetvRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(PlayerUiState())
    val uiState: StateFlow<PlayerUiState> = _uiState

    fun loadChannel(channelId: Int) {
        viewModelScope.launch {
            _uiState.value = PlayerUiState(isLoading = true)
            try {
                val result = repository.getChannel(channelId)
                result.onSuccess { channel ->
                    val countryResult = channel.country?.code?.let {
                        repository.getChannelsByCountry(it)
                    }
                    val related = countryResult?.getOrNull()?.channels
                        ?.filter { it.id != channelId }
                        ?.take(10) ?: emptyList()

                    _uiState.value = PlayerUiState(
                        channel = channel,
                        relatedChannels = related,
                        isLoading = false,
                    )
                }.onFailure { e ->
                    _uiState.value = PlayerUiState(
                        isLoading = false,
                        error = e.message ?: "Failed to load channel"
                    )
                }
            } catch (e: Exception) {
                _uiState.value = PlayerUiState(
                    isLoading = false,
                    error = e.message,
                )
            }
        }
    }
}
